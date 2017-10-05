//**
// * SMSCountry vTiger Integration 
// * (c) KINAMU Business Solutions AG 2009
// * 
// * Parts of this code are (c) 2006. RustyBrick, Inc.  http://www.rustybrick.com/
// * Parts of this code are (c) 2008 vertico software GmbH  
// * Parts of this code are (c) 2009 Copyright (c) 2009 Anant Garg (anantgarg.com | inscripts.com)
// * Parts of this code are (c) 2009 abcona e. K. Angelo Malaguarnera E-Mail admin@abcona.de
// * Parts of this code are (c) 2011 Blake Robertson http://www.blakerobertson.com
// * Parts of this code are (c) 2012 Patrick Hogan askhogan@gmail.com
// * http://www.sugarforge.org/projects/yaai/
// * Contribute To Project: http://www.github.com/blak3r/yaai
// * 
// * This program is free software; you can redistribute it and/or modify it under
// * the terms of the GNU General Public License version 3 as published by the
// * Free Software Foundation with the addition of the following permission added
// * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
// * IN WHICH THE COPYRIGHT IS OWNED BY vTiger, vTiger DISCLAIMS THE WARRANTY
// * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
// * 
// * This program is distributed in the hope that it will be useful, but WITHOUT
// * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
// * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
// * details.
// * 
// * You should have received a copy of the GNU General Public License along with
// * this program; if not, see http://www.gnu.org/licenses or write to the Free
// * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
// * 02110-1301 USA.
// * 
// * You can contact KINAMU Business Solutions AG at office@kinamu.com
// * 
// * The interactive user interfaces in modified source and object code versions
// * of this program must display Appropriate Legal Notices, as required under
// * Section 5 of the GNU General Public License version 3.
// * 
var preferredLanguage = "te";
var flg=false;
var transliterationControl;
google.load("elements", "1", { packages: "transliteration" , nocss : true, callback : "onLoad"  });
//var a = {
//	sourceLanguage: "en",
//	//destinationLanguage: ["te", "hi", "kn", "ml", "ta", "ar", "ur", "ti", "sr", "si", "ru", "sa", "pa", "fa", "or", "ne", "mr", "gu", "el", "zh", "bn", "am"],
//	transliterationEnabled: false,
//	shortcutKey: "ctrl+g"
//};
//transliterationControl = new google.elements.transliteration.TransliterationControl(a);
function onLoad() {
	var a = {
		sourceLanguage: "en",
		//destinationLanguage: ["te", "hi", "kn", "ml", "ta", "ar", "ur", "ti", "sr", "si", "ru", "sa", "pa", "fa", "or", "ne", "mr", "gu", "el", "zh", "bn", "am"],
		transliterationEnabled: false,
		shortcutKey: "ctrl+g"
	};
//	transliterationControl = new google.elements.transliteration.TransliterationControl(a);
//	transliterationControl.makeTransliteratable(["message"]);
//	transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType.SERVER_UNREACHABLE, serverUnreachableHandler);
//	transliterationControl.addEventListener(google.elements.transliteration.TransliterationControl.EventType
//		.SERVER_REACHABLE,
//		serverReachableHandler);

	jQuery("#massSave #msgtype_list_chzn").hide();
	jQuery("#massSave #lbllanguage").hide();
	jQuery("#counter").html("0/160");		
	jQuery('#massSave #message').keyup(updateCount);
	jQuery('#massSave #message').keydown(updateCount);
	
	$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
	jQuery("#massSave #msgtype_list").attr('disabled',true);
	jQuery("#massSave #msgtype_list").change(function(){
			preferredLanguage = jQuery("#msgtype_list").val();
			updateCount();
		});
	jQuery('#massSave #chktranslator').bind('click',
		function (e)
		{
			var Tckbox = jQuery('#massSave input[name=chktranslator]:checked').val();
			jQuery("#msgtype_list").attr('disabled',true);
			jQuery("#massSave #msgtype_list_chzn").hide();
			jQuery("#massSave #lbllanguage").hide();
			if((Tckbox == '1')){
				jQuery("#massSave #lbllanguage").show();
				jQuery("#massSave #msgtype_list_chzn").show();

				jQuery("#msgtype_list").attr('disabled',false);
				//transliterationControl.disableTransliteration();
				//transliterationControl.enableTransliteration();
				new_trans(e);
			}
			else
			{
				//transliterationControl.disableTransliteration();
				//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
			}
			updateCount();
		});
	jQuery('#massSave .btn-SmsSend').bind('click',
		function ()
		{
			if($("#massSave [name='post_id']").val()=='')
			{
				alert("Please select the from id to send");
				return false;
			}
			else if($("#massSave [name='fields[]']").val()=='' || $("#massSave [name='fields[]']").val()==null)
			{
				alert("Please select the phone number fields to send");
				return false;
			}
			else if($("#massSave [name='message']").val()=='')
			{
				alert("Please type the message");
				return false;
			}
			//alert($("#massSave [name='post_id']").val());
			return true;
		});
	jQuery('#massSave #message').bind('blur change keypress paste input',
		function (e)
		{	
			var Tckbox = jQuery('#massSave input[name=chktranslator]:checked').val();
			if((Tckbox == '1')){
				preferredLanguage = jQuery("#msgtype_list").val();
				if(preferredLanguage!='en')
				{
					//transliterationControl.makeTransliteratable(["message"]);
					//transliterationControl.disableTransliteration();
					//transliterationControl.enableTransliteration();
					//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, preferredLanguage);
					new_trans(e);
				}
				else
				{
					$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
					//transliterationControl.disableTransliteration();
					//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
				}
			}
			else{
				$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
			}
			updateCount();
	});

	jQuery('#massSave #message').bind('focusout',
		function (e)
		{	
			var Tckbox = jQuery('#massSave input[name=chktranslator]:checked').val();
			if((Tckbox == '1')){
				preferredLanguage = jQuery("#msgtype_list").val();
				if(preferredLanguage!='en')
				{
					//transliterationControl.makeTransliteratable(["message"]);
					//transliterationControl.disableTransliteration();
					//transliterationControl.enableTransliteration();
					//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, preferredLanguage);
					var response = '';
					$.ajax(
					{
						type: "GET",   
						url: 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='+ preferredLanguage +'&dt=t&q='+ $('#message').val(),
						async: true,
						success: function(response, status, xhr) {
							t='';
							for(i=0;i<response[0].length;i++)
							{
								t = t + response[0][i][0];
							}
							$('#message').val(t + " ");
						}
					});
				}
				else
				{
					//transliterationControl.disableTransliteration();
					//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
				}
			}
			else{
				$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
			}
			updateCount();
	});

	jQuery('#massSave #template_list').bind('blur change keypress paste input',
		function ()
		{	
			template_list = jQuery("#template_list").val();
			jQuery("#massSave #message").val(template_list);
			updateCount();
	});
}
function serverUnreachableHandler(a) { console.log( "Transliteration Server unreachable"); }
function serverReachableHandler(a) { document.getElementById("errorDiv").innerHTML = ""; }

google.setOnLoadCallback(onLoad);
	function chktranslatorclick(){
					var Tckbox = $("input[name=chktranslator]:checked").val();
					$("#msgtype_list").attr("disabled",true);
					jQuery("#massSave #msgtype_list_chzn").hide();
					jQuery("#massSave #lbllanguage").hide();
					if((Tckbox == "1")){
						$("#msgtype_list").attr("disabled",false);
						jQuery("#massSave #msgtype_list_chzn").show();
						jQuery("#massSave #lbllanguage").show();
						send_text_focusin();
					}
					else
					{
						$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
						//transliterationControl.disableTransliteration();
						//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
					}
					updateCount();
		   }
	function send_text_focusin()
			{
				var Tckbox = $("input[name=chktranslator]:checked").val();
				if((Tckbox == "1")){
					if(!flg)
					{
						flg=true;
					
					
					var a = {
						sourceLanguage: "en",
						//destinationLanguage: ["te", "hi", "kn", "ml", "ta", "ar", "ur", "ti", "sr", "si", "ru", "sa", "pa", "fa", "or", "ne", "mr", "gu", "el", "zh", "bn", "am"],
						transliterationEnabled: false,
						shortcutKey: "ctrl+g"
					};
					//transliterationControl = new google.elements.transliteration.TransliterationControl(a);
					}
					preferredLanguage = $("#msgtype_list").val();
					if(preferredLanguage!="en")
					{
						new_trans();
						//transliterationControl.makeTransliteratable(["message"]);
						//transliterationControl.disableTransliteration();
						//transliterationControl.enableTransliteration();
						//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, preferredLanguage);
					}
					else
					{
						//transliterationControl.disableTransliteration();
						//transliterationControl.setLanguagePair(google.elements.transliteration.LanguageCode.ENGLISH, "hi");
					}
				}
				else{
					$('#massSave #msgtype_list option[value=en]').attr('selected','selected');
				}
			}
	function updateCount() {
		var thisInstance = this;
		var cs = jQuery('#massSave #message').val().length;

		var str = jQuery("#massSave #message").val();
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
		
		var Tckbox = jQuery('#massSave input[name=chktranslator]:checked').val();
		preferredLanguage = jQuery("#massSave #msgtype_list").val();
		if((Tckbox != '1' && otherlang==false) || (preferredLanguage=="en" && otherlang==false))
		{
			if(cs>160)
				jQuery('#massSave #counter').html(cs +"/154");
			else
				jQuery('#massSave #counter').html(cs +"/160");
		}
		else
		{
			if(cs>70)
				jQuery('#massSave #counter').html(cs +"/67");
			else
				jQuery('#massSave #counter').html(cs +"/70");
		}
	}

	function new_trans(e)
	{
		//if(!e)
			return;
		preferredLanguage = jQuery("#massSave #msgtype_list").val();
		if(preferredLanguage!='en' && e.which === 32)//&& e.which === 32
		{
			var data = new Array(); 
			var response = '';
			$.ajax(
			{
				type: "GET",   
				url: 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl='+ preferredLanguage +'&dt=t&q='+ $('#message').val(),
				async: true,
				success: function(response, status, xhr) {
					$('#message').val(response[0][0][0] + " ");
				}
			});


		}
	}
	
	
