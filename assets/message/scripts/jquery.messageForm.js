/**
 * jQuery Translator message viewer/editor plugin file.
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * 
 */

(function ($) {

	var attributes;

	attributes = {
			
			init: function(options)
			{
				$dialog = $(this);
				
				settings = $.extend({
					id: '',
					loadingClass: 'loading',
					loading: true,
					translateText: "Save",
				}, options || {});

				$.fn.tMessageForm.settings($dialog, settings);
				$dialog.tMessageForm("message", "");
				$dialog.find("form").each(function(){
					this.reset();
				});
				$dialog.tMessageForm("scenario", settings.scenario);
				$dialog.tMessageForm("loading", settings.loading);

				return $dialog;
			},
			
			id: function(value) 
			{
				var element = this.find("input#Message_id");
				if(value)
				{
					return element.val(value);
				}
				return element.val("");
			},
			
			language: function(value)
			{
				settings = $.fn.tMessageForm.settings(this);
				if(settings.scenario === 'update')
				{
					var element = this.find("p#Message_language");
					if(value)
					{
						return element.text(value);
					}
					return element.text("");
				}
			},
			
			language_id: function(value) 
			{
				settings = $.fn.tMessageForm.settings(this);
				if(settings.scenario === 'update')
				{
					var element = this.find("input#Message_language_id");
					if(value)
					{
						return element.val(value);
					}
					return element.val("");
				}
				else
				{
					var element = this.find("select#Message_language_id");
					if(value)
					{
						element.empty();
						$.each(value, function (value, attributes) {
							element.append($("<option></option>").val(value).text(attributes.text).attr('selected', attributes.selected));
						});
						return element;
					}
					var values = [];
					element.children(":selected").each(function(){
						values[$(this).val()] = $(this).text("");
					});
					return values;
				}
			},
			
			source_language_id: function(value)
			{
				var element = this.find("input#MessageSource_language_id");
				if(value)
				{
					return element.val(value);
				}
				return element.val("");
			},
			
			source_language: function(value)
			{
				var element = this.find("p#MessageSource_language");
				if(value)
				{
					return element.text(value);
				}
				return element.text("");
			},
			
			message: function(value) 
			{
				var element = this.find("textarea#MessageSource_message");
				if(value)
				{
					return element.text(value);
				}
				return element.text("");
			},
			
			translation: function(value) 
			{
				var element = this.find("textarea#Message_translation");
				if(value)
				{
					return element.text(value);
				}
				return element.text("");
			},
			
			scenario: function(scenario)
			{
				settings = $.fn.tMessageForm.settings(this);
				if(settings.scenario !== scenario)
				{
					settings.scenario = scenario;
					switch(scenario)
					{
						case 'update':
							this.find("select#Message_language_id").replaceWith('<input id="Message_language_id" type="hidden" name="Message[language_id]"></input><p id="Message_language"></p>');
							this.find("input[type='submit']").val(settings.saveText);
							this.find("input[name='_method']").val("PUT");
							break;
						default:
							this.find("input#Message_language_id p#Message_language").replaceWith('<select id="Message_language_id" name="Message[language_id]"></select>');
							this.find("input[type='submit']").val(settings.createText);
							this.find("input[name='_method']").val("POST");
							break;
					}
				}
				return this;
			},
			
			status: function(status)
			{
				var settings = $.fn.tMessageForm.settings(this),
					$status = this.find("p#"+settings.id);
				if(status && status.message)
				{
					$status.text(status.message);
					$status.removeClass('hide error warning success');
					if(status.success)
					{
						switch(status.success)
						{
							case 'success':
								$status.addClass('success');
								break;
							case 'warning':
								$status.addClass('warning');
								break;
							default:
								$status.addClass('error');
						}
					}
				}
				else
				{
					$status.text("");
					$status.addClass('hide');
					$status.removeClass('error warning success');
				}
			},
			
			title: function(title)
			{
				this.dialog("option", "title", title);
			},
			
			submit: function()
			{
				var $dialog = $(this),
					settings = $.fn.tMessageForm.settings(this);
					$form = this.find('form');

				$.ajax({
					url: $form.attr('action'),
					type: $form.attr('method'),
					dataType: 'json',
					data: $form.serialize(),
					beforeSend: function(){
						if(settings.beforeSubmit === undefined || settings.beforeSubmit($dialog, $form))
						{
							$dialog.tMessageForm('status');
							$form.addClass(settings.loadingClass);
							return true;
						}
						return false;
					},
					success: function(data){
						if(settings.submitSuccess === undefined || settings.submitSuccess($dialog, $form, data))
						{
							$.each(data, function(attribute, value) {
								$dialog.tMessageForm(attribute, value);
							});
							return true;
						}
						return false;
					},
					error: function(jqXHR, textStatus, errorThrown){
						if(settings.submitError === undefined || settings.submitError($dialog, $form, jqXHR, textStatus, errorThrown))
						{
							$dialog.tMessageForm('status', 'error', errorThrown);
							return true;
						}
						return false;
					},
					complete: function(){
						if(settings.afterSubmit === undefined || settings.afterSubmit($dialog, $form))
						{
							$form.removeClass(settings.loadingClass);
							return true;
						}
						return false;
					}
				});
			},
			
			open: function(href)
			{
				var $dialog = $(this),
					settings = $.fn.tMessageForm.settings(this);
					$form = this.find('form');

				$.ajax({
					url: href,
					type: 'get',
					dataType: 'json',
					beforeSend: function(){
						if(settings.beforeOpen === undefined || settings.beforeOpen($dialog, $form))
						{
							$form.addClass(settings.loadingClass);
							$dialog.dialog('open');
							return true;
						}
						return false;
					},
					success: function(data){
						if(settings.openSuccess === undefined || settings.openSuccess($dialog, $form, data))
						{
							$.each(data, function(attribute, value) {
								$dialog.tMessageForm(attribute, value);
							});
							return true;
						}
						return false;
					},
					error: function(jqXHR, textStatus, errorThrown){
						if(settings.openError === undefined || settings.openError($dialog, $form, jqXHR, textStatus, errorThrown))
						{
							$dialog.tMessageForm('status', 'error', errorThrown);
							return true;
						}
						return false;
					},
					complete: function(){
						if(settings.afterOpen === undefined || settings.afterOpen($dialog, $form))
						{
							$form.removeClass(settings.loadingClass);
							return true;
						}
						return false;
					}
				});
			}

	};

	$.fn.tMessageForm = function(method) 
	{
		if(attributes[method]) 
		{
			return attributes[method].apply(this, Array.prototype.slice.call(arguments, 1));
		}
		else if(typeof method === 'object' || !method) 
		{
			return attributes.init.apply(this, arguments);
		}
		else
		{
			var error = {};
			error[method] = Array.prototype.slice.call(arguments, 1);
			$.fn.yiiactiveform.updateSummary(this.find("form"), error);
		}
	};
	
	/**
	 * Returns the configuration for the specified form.
	 * The configuration contains all needed information to perform ajax-based validation.
	 * @param form jquery the jquery representation of the form
	 * @return object the configuration for the specified form.
	 */
	$.fn.tMessageForm.settings = function (form, data) {
		if(data != undefined)
		{
			return $(form).data('tMessageFormSettings', data);
		}
		return $(form).data('tMessageFormSettings');
	};

})(jQuery);