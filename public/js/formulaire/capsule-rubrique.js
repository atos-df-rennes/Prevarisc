$(document).ready(function() {
    $('.add-rubrique').on('click', function() {
        const form = this.closest('form')
        const capsuleRubrique = form.closest('.objet').id
        const savedRubriquesDiv = $('#'+capsuleRubrique+' .saved-rubriques')
        const savedRubriquesTitlesDiv = $('#'+capsuleRubrique+' .titles')

        const formData = $(form).serialize()

        $.ajax({
            url: '/formulaire/add-rubrique',
            data: formData+'&capsule_rubrique='+capsuleRubrique,
            type: 'POST',
            success: function(data) {
                const parsedData = JSON.parse(data)

                // On crée la table uniquement si elle n'existe pas
                if (savedRubriquesDiv.children().length === 0) {
                    savedRubriquesTitlesDiv.append(`
                        <div class="span6 offset2">
                            <h3>Liste des rubriques</h3>
                        </div>
                    `)
                    savedRubriquesDiv.append(getTableElement())
                }

                const table = savedRubriquesDiv.children('table')
                table.append(getRowElement(parsedData))

                form.reset()
            },
            error: function() {
                return false
            }
        })
        return false
    })

    $('.delete-rubrique').on('click', function(e) {
        e.preventDefault()
        deleteRubrique(this)
    })
})

function deleteRubrique(element) {
    const id = element.getAttribute('data-id')
    const nom = element.getAttribute('data-nom')
    
    $('#dialog-supp').dialog("destroy");
    $("#dialog-supp").remove();

    const dialog_supp = $("<div id='dialog-supp'></div>").appendTo("body")
    dialog_supp.html('Vous êtes sur le point de supprimer la rubrique "' + nom + '".')
    dialog_supp.dialog({
        title: "Suppression d'une rubrique",
        width: 650,
        draggable: false,
        resizable: false,
        modal: true,
        buttons: [
            {
                text: 'Supprimer',
                class: 'btn btn-danger',
                click: function() {
                    const parentDiv = $(element).parent().parent().parent()
                    const parentTable = $(element).closest('table')
                    const nbOfRows = parentTable.children('tbody').children('tr').length

                    const parentObject = element.closest('.objet').id
                    let parentDivTitleDiv = $('#'+parentObject+' .titles .span6.offset2')

                    $.ajax({
                        url: '/formulaire/delete-rubrique/rubrique/'+id,
                        type: 'POST',
                        success: function() {
                            if (nbOfRows === 1) {
                                parentTable.remove()
                                parentDivTitleDiv.remove()
                            } else {
                                parentDiv.remove()
                            }
                        },
                        error: function() {
                            return false
                        }
                    })

                    dialog_supp.dialog("close")
                    $("#dialog-supp").html('')

                    return false
                }
            },
            {
                text: 'Annuler',
                class: 'btn',
                click: function() {
                    dialog_supp.dialog("close")
                    $("#dialog-supp").html('')
                }
            }
        ]
    })
}

function getTableElement() {
    return `<table class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th></th>
                <th>Nom de la rubrique</th>
                <th>Afficher la rubrique par défaut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>`
}

function getRowElement(parsedData) {
    let defaultDisplay = ``;
    if (parsedData.DEFAULT_DISPLAY === 1) {
        defaultDisplay =
            `<div class='text-center'>
                <i class='icon-ok'></i>
            </div>`
    }

    return `<tr id=`+parsedData.ID_RUBRIQUE+`>
        <td class='tdMove'><i class="icon-move"></i></td>
        <td>`
        +parsedData.NOM+
        `</td>
        <td id='default-display'>`
        +defaultDisplay+
        `</td>
        <td id='actions'>
            <div class='text-center'>
                <a href='/formulaire/edit-rubrique/rubrique/`+parsedData.ID_RUBRIQUE+`'>
                    <i title='Modifier' class='icon-pencil'></i>
                </a>
                <button data-id='`+parsedData.ID_RUBRIQUE+`' data-nom='`+parsedData.NOM+`' class='btn btn-link delete-rubrique' onclick='return deleteRubrique(this)'>
                    <i title='Supprimer' class='icon-trash'></i>
                </button>
            </div>
        </td>
    </tr>`
}