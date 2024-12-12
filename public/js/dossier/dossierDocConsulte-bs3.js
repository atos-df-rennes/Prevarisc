$(document).ready(function(){
	$('.date').live('click', function() {
		$(this).datepicker({showOn:'focus', dateFormat: 'dd/mm/yy', firstDay: 1}).focus();
	});

	$(".cancelDoc").live('click',function(){
		var nomTab = $(this).parent().attr('id').split('_');
		if(nomTab.length == 3){
			var nom = nomTab[1]+"_"+nomTab[2];
		}else{
			var nom = nomTab[1]+"_"+nomTab[2]+"_aj";
		}	
		switch($("#tmp").val()){
			case "new":
				$("#div_input_"+nom).fadeOut();
				$("#div_edit_"+nom).fadeIn();
				$("#check_"+nom).removeAttr('checked');
				$("#ref_"+nom).attr('readonly','true').attr('value','');
				$("#date_"+nom).attr('readonly','true').attr('value','');
			break;
			case "edit":
				$("#modif_"+nom).fadeIn();
				$("#valid_"+nom).hide();
				$("#date_"+nom).attr('readonly','true').attr('disabled','disabled').attr('value',$("#tmpDate").val());
				$("#ref_"+nom).attr('readonly','true').attr('value',$("#tmpRef").val());
			break;
			case "ajoutDoc":
				$("#formNewDoc").fadeOut(function(){
					$("#docAjout").fadeIn();						
				});
				$("#libelleNewDoc").attr('value','');
			break;
		}
		$("#dossier_Pdroite").showModif(nom);
		$("#dossier_Pdroite").activeCheck(nom);
		$("#tmpRef").attr('value','');
		$("#tmpDate").attr('value','');
		$("#tmp").attr('value','');
		return false;
	});
	
	//déclaration de la boite de dialog pour l'ajout d'un document ne faisant pas parti de la liste de base
	$("#dialogDocConsulte").dialog({
		resizable: false,
		height:300,
		width:900,
		autoOpen: false,
		modal: true,
		title: 'Ajouter un document consulté',
		buttons: [
			{
				text: 'Enregistrer le document',
				class: 'btn btn-success',
				click: function() {
					if ($("#libelleNewDoc").val() == '') {
						$("#libelleNewDoc").focus();
						return false;
					} else {
						$(this).ajoutDocDialog($("#natureDocAjout").val());
						$(this).dialog('close');
					}

					$(this).dialog('close');
				}
			},
			{
				text: 'Annuler',
				class: 'btn btn-default',
				click: function() {
					$(this).dialog('close');
				}
			}
		],
		close: function(_event, _ui){
			$("body").css('overflow','auto');
			$("#libelleNewDoc").val('');
			$("#natureDocAjout").val('');
		}
	});
	
	//déclaration de la boite de dialog permettant la confirmation de la suppression d'un document ajouté de faisant parti de la liste de base
	$("#dialogConfirmSuppDoc").dialog({
		resizable: false,
		height:200,
		width:450,
		autoOpen: false,
		modal: true,
		title: 'Voulez vous vraiment supprimer ce document ?',
		buttons: [
			{
				text: 'Supprimer',
				class: 'btn btn-danger',
				click: function() {
					//ici on supprime dans la base de données le document lié puis on reinitialise la ligne
					$.ajax({
						url: "/dossier/suppdoc",
						data: "docInfos="+$("#docInfos").val()+"&idDossier="+$("#idDossier").val(),
						type:"POST",
						beforeSend: function(){
							//VERIFICATION SUR L'integrité des données
						},
						success: function(){
							displayActionButtons($("#edit_"+$("#docInfos").val()))

							var tabInfos = $("#docInfos").val().split('_');
							if (tabInfos.length == 2) {
								//doc de base
								$("#ref_"+$("#docInfos").val()).val('');
								$("#date_"+$("#docInfos").val()).val('');

								$("#div_input_"+$("#docInfos").val()).hide();
								$("#check_"+$("#docInfos").val()).removeAttr('checked');
								$("#check_"+$("#docInfos").val()).removeAttr('disabled');
								$("#dossier_Pdroite").activeCheck('');
								$("#dossier_Pdroite").showModif('');
							} else {
								//doc ajouté
								$("#"+$("#docInfos").val()).remove();
								$("#dossier_Pdroite").showModif('.');
								$("#dossier_Pdroite").activeCheck('');
							}

							return false;
						},
						error: function(){
							return false;
						}
					});

					$(this).dialog('close');
				}
			},
			{
				text: 'Annuler',
				class: 'btn btn-default',
				click: function() {
					$(this).dialog('close');
				}
			}
		],
		close: function(_event, _ui){
			$("body").css('overflow','auto');
		}
	});
	
	$(".docAjout").live('click',function(){
		//permet d'afficher la boite de dialogue pour l'ajouter de documents consultés
		var tabNature= $(this).attr('id').split('_');
		var idNature = tabNature[1];
		$("#natureDocAjout").val(idNature);
		$("#dialogDocConsulte").dialog('open');
		return false;
	});
	
	//utilisé lorsque l'on ajoute un document à la liste des docs.
	$("#AjoutDocValid").click(function(){
		if($("#libelleNewDoc").val() == ''){
			$("#libelleNewDoc").focus();
			return false;
		}

		$.ajax({
			url: "/dossier/fonction",
			data: "do=ajoutDocValid&libelledoc="+$("#libelleNewDoc").val()+"&idDossier="+$("#idDossier").val()+"&natureDocAjout"+$("#natureDocAjout").val(),
			type:"POST",			
			beforeSend: function(){
				//VERIFICATION SUR L'integrité des données
			},
			success: function(affichageResultat){
				$("#listeDocs").append(affichageResultat);
				$("#libelleNewDoc").attr('value','');
				$("#dossier_Pdroite").activeCheck('qsd');

				return false;
			},
			error: function(){
				return false;
			}
		});
		return false;
	});

	
	$(".editDoc").live('click',function(){
		displayActionButtons($(this))

		return false;
	});

	//gestion de la suppression des documents consultés
	$(".deleteDoc").live('click',function(){
		var idDoc = $(this).attr('name');
		$('#docInfos').val(idDoc);
		$("#dialogConfirmSuppDoc").dialog('open');
		$("#affichageDocSupp").html($("#"+idDoc).children('.libelle').html());
		$("#refDocSupp").html($("#ref_"+idDoc).val());
		$("#dateDocSupp").html($("#date_"+idDoc).val());
		return false;
	});

	function displayActionButtons(element) {
		var nomTab = element.parent().attr('id').split('_');
		var nature = nomTab[1];

		if (nomTab.length == 3) {
			var nom = nomTab[2];
		} else {
			var nom = nomTab[2]+"_aj";
		}

		nom = nature+"_"+nom;

		$("#tmpRef").attr('value',$("#ref_"+nom).val());
		$("#tmpDate").attr('value',$("#date_"+nom).val());
		$("#tmp").attr('value','edit');
		
		$("#ref_"+nom).removeAttr('readonly');
		$("#date_"+nom).removeAttr('readonly').removeAttr('disabled');
		 
		$("#modif_"+nom).hide();
		$("#valid_"+nom).fadeIn();

		$("#libelleView_"+nom).hide();
		$("#libelle_"+nom).show();

		$("#dossier_Pdroite").hideModif(nom);
		$("#dossier_Pdroite").blockCheck(nom);
	}
});