/**
 * jQuery install component plugin file.
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * 
 */

(function ($) {

	var methods;

	methods = {
			
			init: function(options)
			{
				var $installer = $(this),
					settings = $.extend({
						dialogId: '',
						statusId: '',
						progressBarId: '',
						component: '',
						url: document.URL,
						hiddenCssClass: 'hide',
						errorCssClass: 'error',
						noticeCssClass: 'warning',
						successCssClass: 'success',
						dialogNoCloseClass: 'no-close',
						loadingText: 'Installing...',
						cancelText: 'Cancel',
						confirmText: 'Confirm',
						closeText: 'Close'
					}, options || {});
				
				settings.dialogSelector = 'div#' + settings.dialogId;
				settings.statusSelector = 'p#' + settings.statusId;
				settings.progressBarSelector = 'div#' + settings.progressBarId;
				
				settings.confirmButtons = {
						'Cancel': function() {
							$(settings.dialogSelector).dialog("close");
						},
						'Confirm': function() {
							$installer.tInstaller("install", true);
						}
					};
				settings.completeButtons = {
					'Close': function() {
						$(settings.dialogSelector).dialog("close");
						$dialog.tInstaller("status");
					}
				};

				$.fn.tInstaller.settings($installer, settings);

				return $installer;
			},
			
			status: function(status)
			{
				var settings = $.fn.tInstaller.settings(this),
					$status = $($.find(settings.statusSelector));

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
			
			install: function(overwrite) 
			{
				var settings = $.fn.tInstaller.settings(this);

				$.ajax({
					url: settings.url,
					type: 'POST',
					data: {
							component: settings.component,
							overwrite: (overwrite === undefined ? false : true),
					},
					context: $(this),
					beforeSend: function(){
						var $installer = $(this),
							settings = $.fn.tInstaller.settings(this),
							$dialog = $($.find(settings.dialogSelector)),
							$progress = $($.find(settings.progressBarSelector));
						
						$installer.tInstaller('status', {status: 'normal', message: settings.loadingText});
						$dialog.dialog('open');
						$dialog.dialog("option", "buttons", settings.confirmButtons);
						$progress.removeClass(settings.hiddenCssClass);
						$dialog.dialog('option', 'dialogClass', settings.dialogNoCloseClass);
						$dialog.dialog("option", "position", ['center', 'center']);
						return true;
					},
					success: function(responseData){
						var $installer = $(this),
							settings = $.fn.tInstaller.settings(this),
							$dialog = $(settings.dialogSelector);
						
						try
						{
							$installer.tInstaller('status', $.parseJSON(responseData));
						}
						catch(e)
						{
							$installer.tInstaller('status', {status: 'error', message: 'An error occurred while attempting to read the server\'s response!'});
						}
						$dialog.dialog("option", "position", ['center', 'center']);
						return true;
					},
					error: function(jqXHR, textStatus, errorThrown){
						var settings = $.fn.tInstaller.settings(this),
							$installer = $(this),
							$dialog = $($.find(settings.dialogSelector));
						$installer.tInstaller('status', {'status': 'error', 'message': errorThrown});
						$dialog.dialog("option", "position", ['center', 'center']);
						return true;
					},
					complete: function(){
						var settings = $.fn.tInstaller.settings(this),
							$dialog = $($.find(settings.dialogSelector)),
							$progress = $dialog.find(settings.progressBarSelector);

						$progress.addClass(settings.hiddenCssClass);
						$dialog.dialog('option', 'dialogClass', '');
						$dialog.dialog("option", "position", ['center', 'center']);
						return true;
					}
				});
			}

	};

	$.fn.tInstaller = function(method) 
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
			$.error('Method ' + method + ' does not exist on jQuery.tInstaller');
			return false;
		}
	};
	
	/**
	 * Returns the configuration for the specified selection handler.
	 * 
	 * @param selection the selection handler
	 * @return object the configuration for the specified selection handler.
	 */
	$.fn.tInstaller.settings = function (selection, data) {
		if(data != undefined)
		{
			return $(selection).data('tInstallerSettings', data);
		}
		return $(selection).data('tInstallerSettings');
	};

})(jQuery);