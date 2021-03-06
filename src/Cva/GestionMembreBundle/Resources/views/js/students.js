




$(function() {
	$('#editStudent').hide();
	$('.modal').on('hide.bs.modal', refreshStudentDetails);
	$('#confirm-delete').on('show.bs.modal', function(e) {
		var $modal = $(this);
		var $btn = $(this).find('.btn-ok');
		var $origin = $(e.relatedTarget);
        if($origin.data('route') == undefined){
			$btn.attr('href', "#");
			var actionOnClick = function (e) {
				e.preventDefault();
				$btn.off('click',actionOnClick);
				var a = new Sonic(loader);
				var oldContent = $btn.html();
				$btn.html(a.canvas);
				a.play();
				$.get($origin.data('href'), function () {
					refreshStudentDetails();
                    $modal.modal('hide');
                    a.stop();
                    $btn.html(oldContent);
                })
            };
            $btn.on('click', actionOnClick)
		} else {
			$btn.attr('href', Routing.generate($origin.data('route'), {id: currentStudentId()}));
		}
	});
});

/**
 * Open the modal to create a new payment for the selected student
 */
function newPayment(elem){
	$('#editPaymentModal').modal('show');
	console.log("Open : "+$(elem).data('href'));
	$.get($(elem).data('href'),function(content){ // The elem params should contains a data-href which is the data to
												  // display in our modal.
		$('#editPaymentModalContent').html(content);
	})

}

function voir(resource) {
	$.get(Routing.generate("cva_membership_student_sidebar",{id:parseInt(resource)}), function (msg) {
		var $editStudent = $("#editStudent");
		$editStudent.show();
		$('#voirEtudiant').html(msg);
	});
}

function editStudent(){
	//fixes a bootstrap bug that prevents a modal from being reused
	$('#editStudentModal').modal('show').children("#editStudentModalContent");
	$.get(
		Routing.generate("cva_membership_student_edit_modal",{id:$("#etuid").data("etu-id")}),
		function(response, status, xhr) {
			if (status === 'error') {
				//console.log('got here');
				$('#editStudentModalContent').html('<h2>Oh boy</h2><p>Sorry, but there was an error:' + xhr.status + ' ' + xhr.statusText+ '</p>');
			}else {
				$('#editStudentModalContent').html(response);
			}
			return this;
		}
	);

}

(function fillDataTable() {
				var TableAssemblees = $('#table_adherent').dataTable({
					"oLanguage": {
						"sProcessing":     "Traitement en cours...",
						"sSearch":         "Rechercher&nbsp;:",
						"sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
						"sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
						"sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
						"sInfoPostFix":    "",
						"sLoadingRecords": "Chargement en cours...",
						"sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
						"sZeroRecords":    "Aucun r&eacute;sultat ne correspond à votre recherche.",
						"sEmptyTable":     "Aucune donnée disponible dans le tableau",
						"oPaginate": {
							"sFirst":      "Premier",
							"sPrevious":   "Pr&eacute;c&eacute;dent",
							"sNext":       "&nbsp;Suivant",
							"sLast":       "Dernier"
						},
						"oAria": {
							"sSortAscending":  ": activer pour trier la colonne par ordre croissant",
							"sSortDescending": ": activer pour trier la colonne par ordre décroissant"
						}
					}
				});
})();
