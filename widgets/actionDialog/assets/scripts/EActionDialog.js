/**
 * jQuery EActionDialog plugin file.
 *
 * @author Louis A. DaPrato <l.daprato@gmail.com>
 * 
 */

(function ($) {

	var attributes;

	attributes = {
			
			init: function(options)
			{
				var settings = $.extend({
						requestType: 'get',
						confirmRequestVarName: 'confirm',
						csrfTokenName: '',
						contentId: '',
						progressBarId: '',
						cancelButtonText: 'Cancel',
						confirmButtonText: 'Confirm',
						closeButtonText: 'Close',
						defaultContents: {
							'default': '',
							success: 'Success',
							error: 'Error',
							notice: 'Notice',
							confirm: 'Confirm',
							loading: 'Loading...'
						},
						progressBarClasses: {
							'default': 'action-dialog-hide',
							loading: ''
						},
						contentClasses: {
							'default': '',
							error: 'action-dialog-error',
							notice: 'action-dialog-notice',
							success: 'action-dialog-success',
						},
						progressBarOptions: {},
						dialogOptions: {
							'default': {
								buttons: {
									close: {
										text: 'Close',
										click: function(){
											$(this).dialog("close");
										}
									}
								},
								position: ['center', 'center'],
							},
							confirm: {
								buttons: {
									confirm: {
										text: 'Confirm',
									},
									cancel: {
										text: 'Cancel',
										click: function(){
											$(this).dialog("close");
										}
									}
								},
								position: ['center', 'center'],
							},
							loading: {
								buttons: {},
								position: ['center', 'center'],
							},
						},
					}, options || {});

				if(settings.dialog !== undefined)
				{
					settings.dialog = $(settings.dialog);
					if(settings.dialog.length === 0)
					{
						$.error('Invalid dialog target specified.');
					}
				}
				else
				{
					$.error('A dialog target must be specified.');
				}

				return this.each(function () {
					var $target = $(this),
						localSettings = $.extend({}, settings);
					
					if(localSettings.url === undefined)
					{
						localSettings.url = $target.attr('href');
						
						if(!localSettings.url)
						{
							localSettings.url = document.URL;
						}
					}

					if(localSettings.dialogOptions.confirm.buttons.confirm !== undefined && localSettings.dialogOptions.confirm.buttons.confirm.click === undefined)
					{
						localSettings.dialogOptions = {
								confirm: {
									buttons: {
										confirm: {
											click:function(){
												$target.each(function() {
													$(this).eActionDialog('submit');
												});
						}}}}};
					}
					
					$.fn.eActionDialog.settings($target, localSettings);

					$target.live('click', function(e, eventInfo) {
						e.preventDefault();
						$(this).eActionDialog('open');
					});
				});
			},
			
			collectStatusSettings: function(status, statusSettings, defaultSetting)
			{
				var settings = $.fn.eActionDialog.settings(this);
				
				if(statusSettings === undefined)
				{
					statusSettings = {contentClasses: undefined, progressBarClasses: undefined, dialogOptions: undefined, progressBarOptions: undefined, defaultContents: undefined};
				}
				if(defaultSetting === undefined)
				{
					defaultSetting = 'default';
				}
				
				if(status === undefined)
				{
					status = defaultSetting;
				}
				for(var statusSetting in statusSettings)
				{
					if(settings[statusSetting] === undefined)
					{
						statusSettings[statusSetting] = undefined;
					}
					else if(settings[statusSetting][status] === undefined)
					{
						statusSettings[statusSetting] = settings[statusSetting][defaultSetting];
					}
					else
					{
						statusSettings[statusSetting] = settings[statusSetting][status];
					}
				}
				
				return statusSettings;
			},
			
			status: function(status, content)
			{
				var $target = $(this),
					settings = $.fn.eActionDialog.settings(this),
					$progressBar = settings.dialog.children('div:nth-child(1)'),
					$dialogContent = settings.dialog.children('div:nth-child(2)'),
					statusSettings = $target.eActionDialog('collectStatusSettings', status);

				$progressBar.removeClass();
				if(statusSettings.progressBarClasses !== undefined)
				{
					$progressBar.addClass(statusSettings.progressBarClasses);
				}
				
				$dialogContent.empty();
				$dialogContent.removeClass();
				if(statusSettings.contentClasses !== undefined)
				{
					$dialogContent.addClass(statusSettings.contentClasses);
				}
				
				if(content === undefined)
				{
					if(statusSettings.defaultContents !== undefined)
					{
						$dialogContent.html(statusSettings.defaultContents);
					}
				}
				else 
				{
					$dialogContent.html(content);
				}

				if(statusSettings.progressBarOptions)
				{
					$progressBar.children().progressbar('option', statusSettings.progressBarOptions);
				}
				if(statusSettings.dialogOptions)
				{
					settings.dialog.dialog('option', statusSettings.dialogOptions);
				}
				
				$target.trigger('statusChanged', {status: status, content: content});
			},
			
			open: function()
			{
				var $target = $(this),
					settings = $.fn.eActionDialog.settings(this);

				settings.dialog.dialog('open');
				$target.eActionDialog('sendRequest', $target.parent('form').serialize());
			},
			
			submit: function()
			{
				var $target = $(this),
					settings = $.fn.eActionDialog.settings(this);

				$target.eActionDialog('sendRequest', $target.parent('form').serialize(), true);
			},
			
			sendRequest: function(data, confirm) 
			{
				var $target = $(this),
					settings = $.fn.eActionDialog.settings(this);
				
				// Set dialog status to loading
				$target.eActionDialog('status', 'loading');

				// Init data if not defined
				if(data === undefined)
				{
					data = {};
					typeOfData = 'object';
				}
				else
				{
					typeOfData = typeof data;
				}
				
				additionalData = {};
				if(confirm !== undefined)
				{
					additionalData[settings.confirmRequestVarName] = confirm;
				}
				
				// Add CSRF token if available
				if(settings.csrfToken !== undefined)
				{
					$.extend(additionalData, settings.csrfToken);
		    	}
				
				switch (typeof data) 
				{
					case 'string':
						data += $.param(additionalData);
						break;
					case 'object':
						$.extend(data, additionalData);
						break;
					default:
						break;
				}

				// Send Ajax request
				$.ajax({
					url: settings.url,
					type: settings.requestType,
					dataType: 'json',
					data: data,
					context: $target,
					success: function(responseData){
						$(this).eActionDialog('status', responseData.status, responseData.content);
					},
					error: function(responseData){
						$(this).eActionDialog('status', 'error', responseData.responseText);
					}
				});
			},

	};

	$.fn.eActionDialog = function(method) 
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
			$.error('Method ' + method + ' does not exist in jQuery.eActionDialog');
			return false;
		}
	};
	
	/**
	 * Returns the configuration for the specified selection handler.
	 * 
	 * @param selection the selection handler
	 * @return object the configuration for the specified selection handler.
	 */
	$.fn.eActionDialog.settings = function (target, data) {
		if(data !== undefined)
		{
			return $(target).data('eActionDialog-settings', data);
		}
		return $(target).data('eActionDialog-settings');
	};

})(jQuery);
