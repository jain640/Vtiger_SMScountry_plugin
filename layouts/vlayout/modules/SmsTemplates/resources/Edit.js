/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("SmsTemplates_Edit_Js",{},{
	
	/**
	 * Function to register event for ckeditor for description field
	 */
	registerEventForCkEditor : function(){
		var templateContentElement = jQuery("#templatecontent");
		this.registerFillTemplateContentEvent();
	},
	
	/**
	 * Function which will register module change event
	 */
	registerChangeEventForModule : function(){
		var thisInstance = this;
		var advaceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance();
		var filterContainer = advaceFilterInstance.getFilterContainer();
		filterContainer.on('change','select[name="modulename"]',function(e){
			thisInstance.loadFields();
		});
	},
	
	/**
	 * Function to load condition list for the selected field
	 * @params : fieldSelect - select element which will represents field list
	 * @return : select element which will represent the condition element
	 */
	loadFields : function() {
		var moduleName = jQuery('select[name="modulename"]').val();
		var allFields = jQuery('[name="moduleFields"]').data('value');
		var fieldSelectElement = jQuery('select[name="templateFields"]');
		var options = '';
		for(var key in allFields) {
			//IE Browser consider the prototype properties also, it should consider has own properties only.
			if(allFields.hasOwnProperty(key) && key == moduleName) {
				var moduleSpecificFields = allFields[key];
				var len = moduleSpecificFields.length;
				for (var i = 0; i < len; i++) {
					var fieldName = moduleSpecificFields[i][0].split(':');
					options += '<option value="'+moduleSpecificFields[i][1]+'"';
					if(fieldName[0] == moduleName) {
						options += '>'+fieldName[1]+'</option>';
					} else {
						options += '>'+moduleSpecificFields[i][0]+'</option>';
					}
				}
			}
		}
		
		if(options == '')
			options = '<option value="">NONE</option>';
		
		fieldSelectElement.empty().html(options).trigger("liszt:updated");
		return fieldSelectElement;
		
	},
	
	registerFillTemplateContentEvent : function() {
		jQuery('#templateFields').change(function(e){
			var templateContentElement = jQuery("#templatecontent");
			//var textarea = CKEDITOR.instances.templatecontent;
			var value = jQuery(e.currentTarget).val();
			templateContentElement.val(templateContentElement.val()+value);
			//textarea.insertHtml(value);
		});
	},

/**
	 * Registered the events for this page
	 */
	registerEvents : function() {
		var thisInstance = this;
		var preferredLanguage = "te";
		
		var transliterationControl;
		google.load("elements", "1", { packages: "transliteration" , nocss : true, callback : "onLoad"  });

		jQuery("#counter").html("0/160");		
		jQuery('#templatecontent').keyup(function(e){
			thisInstance.updateCount();
		});
		jQuery('#templatecontent').keydown(function(e){
			thisInstance.updateCount();
		});
		
		jQuery("#templatelanguage").change(function(){
			preferredLanguage = jQuery("#templatelanguage").val();
			thisInstance.updateCount();
		});
		google.setOnLoadCallback(onLoad);

		jQuery('#templatecontent').bind('blur change keypress paste input',
			function ()
			{	
				if (typeof transliterationControl === "undefined") {
					console.log("dd");
					var a = {
						sourceLanguage: "en",
						destinationLanguage: ["te", "hi", "kn", "ml", "ta", "ar", "ur", "ti", "sr", "si", "ru", "sa", "pa", "fa", "or", "ne", "mr", "gu", "el", "zh", "bn", "am"],
						transliterationEnabled: false,
						shortcutKey: "ctrl+g"
					};
					transliterationControl = new google.elements.transliteration.TransliterationControl(a);
					transliterationControl.makeTransliteratable(["templatecontent"]);
					transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType.SERVER_UNREACHABLE, serverUnreachableHandler);
					transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType
						.SERVER_REACHABLE,
						serverReachableHandler);
				}
				preferredLanguage = jQuery("#templatelanguage").val();

				if(preferredLanguage!='en')
				{
					transliterationControl.enableTransliteration();
					transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, preferredLanguage);
					transliterationControl.makeTransliteratable(["templatecontent"]);
					
				}
				else
				{
					transliterationControl.disableTransliteration();
					transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
				}
				thisInstance.updateCount();
		});
		this.registerEventForCkEditor();
		this.registerChangeEventForModule();
		//jQuery('#EditView').validationEngine();
		this._super();
		
		
	},
	updateCount : function() {
		var thisInstance = this;
		var cs = jQuery('#templatecontent').val().length;

		var str = jQuery("#templatecontent").val();
		otherlang = false;
		for (var i = 0; i < cs; i++)
		{
			n = str.charCodeAt(i);
			if (parseInt(n) > 255)
			{
				otherlang = true;
				break;
			}
		}
		
		preferredLanguage = jQuery("#templatelanguage").val();
		if((otherlang==false) || (preferredLanguage=="en" && otherlang==false))
		{
			if(cs>160)
				jQuery('#counter').html(cs +"/154");
			else
				jQuery('#counter').html(cs +"/160");
		}
		else
		{
			if(cs>70)
				jQuery('#counter').html(cs +"/67");
			else
				jQuery('#counter').html(cs +"/70");
		}
	}
});

	
	
	function onLoad() {
		var a = {
			sourceLanguage: "en",
			destinationLanguage: ["te", "hi", "kn", "ml", "ta", "ar", "ur", "ti", "sr", "si", "ru", "sa", "pa", "fa", "or", "ne", "mr", "gu", "el", "zh", "bn", "am"],
			transliterationEnabled: false,
			shortcutKey: "ctrl+g"
		};
		transliterationControl = new google.elements.transliteration.TransliterationControl(a);
		transliterationControl.makeTransliteratable(["templatecontent"]);
		transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType.SERVER_UNREACHABLE, serverUnreachableHandler);
		transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType
			.SERVER_REACHABLE,
			serverReachableHandler);
	}
	function serverUnreachableHandler(a) { console.log( "Transliteration Server unreachable"); }
	function serverReachableHandler(a) { document.getElementById("errorDiv").innerHTML = ""; }
