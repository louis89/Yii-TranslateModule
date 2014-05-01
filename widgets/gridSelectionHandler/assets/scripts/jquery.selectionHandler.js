/**
 * jQuery Translator message viewer/editor plugin file.
 *
 * @author Louis DaPrato <l.daprato@gmail.com>
 * 
 */

(function ($) {

	var attributes;

	attributes = {
			
			init: function(options)
			{
				$dialog = $(this);
				
				settings = $.extend({
					gridId: '',
					relatedGrids: [],
					url: document.URL,
					useAjax: true,
					activeRecordClass: '',
					keys: ['id'],
					keyDelimiter: ',',
					statusId: '',
					hiddenCssClass: 'hide',
					errorCssClass: 'error',
					noticeCssClass: 'warning',
					successCssClass: 'success',
					dialogNoCloseClass: 'no-close',
					loadingText: 'Loading...',
					confirmButtons: {
						'Cancel': function() {
							$dialog.dialog("close");
						},
						'Confirm': function() {
							$dialog.tSelectionHandler("handleSelection");
						}
					},
					completeButtons: {
						'Close': function() {
							$dialog.dialog("close");
							$dialog.tSelectionHandler("status");
						}
					}
				}, options || {});
				
				settings.gridSelector = 'div#' + settings.gridId;
				settings.statusSelector ='p#' + settings.statusId;
				
				if(settings.url[settings.url.length - 1] == '?')
				{
					settings.url = settings.url.slice(0, -1);
				}
				
				$.fn.tSelectionHandler.settings($dialog, settings);

				return $dialog;
			},
			
			status: function(status)
			{
				var settings = $.fn.tSelectionHandler.settings(this),
					$status = $(this).find(settings.statusSelector);

				if(status && status.message)
				{
					$status.text(status.message);
					$status.removeClass([settings.hiddenCssClass, settings.errorCssClass, settings.noticeCssClass, settings.successCssClass].join(' '));
					switch(status.status)
					{
						case 'success':
							$status.addClass(settings.successCssClass);
							break;
						case 'notice':
							$status.addClass(settings.noticeCssClass);
							break;
						case 'error':
							$status.addClass(settings.errorCssClass);
							break;
						default:
							break;
					}
				}
				else
				{
					$status.text("");
					$status.addClass(settings.hiddenCssClass);
					$status.removeClass([settings.errorCssClass, settings.noticeCssClass, settings.successCssClass].join(' '));
				}
			},
			
			serializeSelection: function()
			{
				var settings = $.fn.tSelectionHandler.settings(this),
					$selection = $(settings.gridSelector),
					selection = $selection.yiiGridView('getSelection');
				
				if(selection.length == 0)
				{
					return $($selection.yiiGridView.settings[settings.gridId].filterSelector).serialize();
				}

				data = {};
				data[settings.activeRecordClass] = {};
				
				for(var i = 0; i < selection.length; i++)
				{
					var keys = selection[i].split(settings.keyDelimiter);
					for(var j = 0; j < keys.length; j++)
					{
						if(data[settings.activeRecordClass][settings.keys[j]] === undefined)
						{
							data[settings.activeRecordClass][settings.keys[j]] = [];
						}
						data[settings.activeRecordClass][settings.keys[j]].push(keys[j]);
					}
				}
				
				return $.param(data);
			},
			
			open: function(selection)
			{
				var $dialog = $(this),
					settings = $.fn.tSelectionHandler.settings(this);

				if(selection[settings.activeRecordClass] === undefined)
				{
					settings.activeSelection = $dialog.tSelectionHandler('serializeSelection');
				}
				else
				{
					s = {};
					s[settings.activeRecordClass] = selection[settings.activeRecordClass];
					settings.activeSelection = $.param(s);
				}
				$dialog.tSelectionHandler('sendRequest', settings.activeSelection, true, false);
				$dialog.dialog("option", "buttons", settings.confirmButtons);
				$dialog.dialog('open');
			},
			
			handleSelection: function(selection)
			{
				var $dialog = $(this),
					settings = $.fn.tSelectionHandler.settings(this);

				if(selection !== undefined || settings.activeSelection === undefined)
				{
					settings.activeSelection = (selection === undefined ? $dialog.tSelectionHandler('serializeSelection') : $.param(selection));
				}
				
				$dialog.tSelectionHandler('sendRequest', settings.activeSelection, false, true);
				$dialog.dialog("option", "buttons", settings.completeButtons);
			},
			
			sendRequest: function(data, dryRun, updateGridOnComplete) 
			{
				var settings = $.fn.tSelectionHandler.settings(this);

				data += (data.trim() === '' ? '' : '&') + 'dryRun=' + (dryRun !== undefined && dryRun ? '1' : '0');
				if(settings.useAjax)
				{
					$.ajax({
						url: settings.url,
						type: 'GET',
						data: data,
						context: $(this),
						beforeSend: function(){
							var $dialog = $(this),
								settings = $.fn.tSelectionHandler.settings(this),
								$progress = $dialog.find(settings.progressBarSelector);
							$dialog.tSelectionHandler('status', {status: 'normal', message: settings.loadingText});
							$progress.removeClass(settings.hiddenCssClass);
							$dialog.dialog('option', 'dialogClass', settings.dialogNoCloseClass);
							$dialog.dialog("option", "position", ['center', 'center']);
							return true;
						},
						success: function(responseData){
							var $dialog = $(this);
							try
							{
								$dialog.tSelectionHandler('status', $.parseJSON(responseData));
							}
							catch(e)
							{
								$dialog.tSelectionHandler('status', {status: 'error', message: 'An error occurred while attempting to read the server\'s response!'});
							}
							$dialog.dialog("option", "position", ['center', 'center']);
							return true;
						},
						error: function(jqXHR, textStatus, errorThrown){
							var $dialog = $(this);
							$dialog.tSelectionHandler('status', {'status': 'error', 'message': errorThrown});
							$dialog.dialog("option", "position", ['center', 'center']);
							return true;
						},
						complete: function(){
							var $dialog = $(this),
								settings = $.fn.tSelectionHandler.settings(this),
								$progress = $dialog.find(settings.progressBarSelector);
							if(updateGridOnComplete !== undefined & updateGridOnComplete)
							{
								$(settings.gridSelector).yiiGridView('update');
								for(var i = 0; i < settings.relatedGrids.length; i++)
								{
									$('#'+settings.relatedGrids[i]).yiiGridView('update');
								}
							}
							$progress.addClass(settings.hiddenCssClass);
							$dialog.dialog('option', 'dialogClass', '');
							$dialog.dialog("option", "position", ['center', 'center']);
							return true;
						}
					});
				}
				else
				{
					window.location.href = settings.url + '?' + data;
				}
			}

	};

	$.fn.tSelectionHandler = function(method) 
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
			$.fn.yiiGridView.update(this);
		}
	};
	
	/**
	 * Returns the configuration for the specified selection handler.
	 * 
	 * @param selection the selection handler
	 * @return object the configuration for the specified selection handler.
	 */
	$.fn.tSelectionHandler.settings = function (selection, data) {
		if(data != undefined)
		{
			return $(selection).data('tSelectionHandlerSettings', data);
		}
		return $(selection).data('tSelectionHandlerSettings');
	};

})(jQuery);