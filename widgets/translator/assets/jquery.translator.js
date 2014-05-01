/**
 * jQuery auto translate button plugin file.
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 * 
 */

(function ($) {

	var methods;

	methods = {
			
			init: function(options)
			{
				var $message = $(this),
					settings = $.extend({
						id: '',
						selectors: {},
						url: document.URL,
						loadingCssClass: 'translating',
						hiddenCssClass: 'hide',
					}, options || {});
				
				settings.statusSelector = '#'+settings.id+'-status';
				settings.buttonSelector = '#'+settings.id+'-button';
				
				$.fn.translator.settings($message, settings);

				return $message;
			},
			
			status: function(status)
			{
				var settings = $.fn.translator.settings($.fn.translator.source(this)),
					$status = $(settings.statusSelector);

				if(status && status.message)
				{
					if(status.status === 'success')
					{
						var $target = $(this);
						$target.is(':input') ? $target.val($reponse.message) : $target.text($response.message);
						$status.text("");
						$status.addClass(settings.hiddenCssClass);
					}
					else
					{
						$status.text(status.message);
						$status.removeClass(settings.hiddenCssClass);
					}
				}
				else
				{
					$status.text("");
					$status.addClass(settings.hiddenCssClass);
				}
			},
			
			translate: function(sourceLanguage, targetLanguage, target)
			{
				var $message = $(this),
					settings = $.fn.translator.settings(this);

				if(target !== undefined)
				{
					$target = $(target);
				}
				else if(settings.selectors.target !== undefined)
				{
					$target = $(settings.selectors.target);
				}
				else
				{
					$target = $message;
				}
				
				$.fn.translator.source($target, $message);

				data = {
						message: $message.is(':input') ? $message.val() : $message.text(),
						sourceLanguage: sourceLanguage,
						targetLanguage: targetLanguage
					};
				if(sourceLanguage === undefined)
				{
					$elem = $(settings.selectors.sourceLanguage);
					data.sourceLanguage = $elem.is(':input') ? $elem.val() : $elem.text();
				}
				if(targetLanguage === undefined)
				{
					$elem = $(settings.selectors.targetLanguage);
					data.targetLanguage = $elem.is(':input') ? $elem.val() : $elem.text();
				}

				$.ajax({
					url: settings.url,
					type: 'GET',
					data: data,
					context: $target,
					beforeSend: function(){
						var settings = $.fn.translator.settings($.fn.translator.source(this));
						$(settings.buttonSelector).prop('disabled', true);
						$(this).addClass(settings.loadingCssClass);
						return true;
					},
					success: function(responseData){
						try
						{
							$(this).translator('status', $.parseJSON(responseData));
						}
						catch(e)
						{
							$(this).translator('status', {status: 'error', message: 'An error occurred while attempting to read the server\'s response!'});
						}
						return true;
					},
					error: function(jqXHR, textStatus, errorThrown){
						$(this).translator('status', {status: 'error', message: errorThrown});
						return true;
					},
					complete: function(){
						var settings = $.fn.translator.settings($.fn.translator.source(this));
						$(settings.buttonSelector).prop('disabled', false);
						$(this).removeClass(settings.loadingCssClass);
						return true;
					}
				});
			},

	};

	$.fn.translator = function(method) 
	{
		if(methods[method]) 
		{
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		else if(typeof method === 'object' || !method) 
		{
			return methods.init.apply(this, arguments);
		}
		else
		{
			$.error('Method ' + method + ' does not exist on jQuery.translator');
			return false;
		}
	};
	
	/**
	 * Sets/Gets the configuration for the specified object.
	 * 
	 * @param object the object with a configuration
	 * @return object the configuration for the specified object.
	 */
	$.fn.translator.settings = function (object, data) {
		if(data != undefined)
		{
			return $(object).data('translatorSettings', data);	
		}
		return $(object).data('translatorSettings');
	};
	
	/**
	 * Sets/Gets the translation target's source (source message).
	 * 
	 * @param object the target
	 * @return object the source
	 */
	$.fn.translator.source = function (target, source) {
		if(source != undefined)
		{
			return $(target).data('translatorSource', source);	
		}
		return $(target).data('translatorSource');
	};

})(jQuery);