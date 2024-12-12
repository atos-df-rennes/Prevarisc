$(document).ready(function() {
    $('.add-rubrique').on('click', function() {
        const form = this.closest('form')
        const capsuleRubrique = form.closest('.objet').id
        const savedRubriquesDiv = $('#'+capsuleRubrique+' .saved-rubriques:not(.hidden)')
        const rubriquesDiv = $('#'+capsuleRubrique+' .rubriques')

        const formData = $(form).serialize()

        $.ajax({
            url: '/formulaire/add-rubrique',
            data: formData+'&capsule_rubrique='+capsuleRubrique,
            type: 'POST',
            success: function(data) {
                const parsedData = JSON.parse(data)

                // On crée la table uniquement si elle n'existe pas
                if (savedRubriquesDiv.length === 0) {
                    rubriquesDiv.append($('#saved-rubriques-prototype').removeAttr('id').removeClass('hidden'))
                }

                const table = $('#'+capsuleRubrique+' .saved-rubriques:not(.hidden) table tbody')
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
    
    $("#name-rubrique").html(nom)
    $("#delete-rubrique").click(function() {
        const parentDiv = $(element).parent().parent().parent()
        const parentTable = $(element).closest('.saved-rubriques:not(.hidden)')
        const nbOfRows = parentTable.children('.panel').children('table').children('tbody').children('tr').length

        $.ajax({
            url: '/formulaire/delete-rubrique/rubrique/'+id,
            type: 'POST',
            success: function() {
                if (nbOfRows === 1) {
                    parentTable.remove()
                } else {
                    parentDiv.remove()
                }

                $("#modal-delete-rubrique").modal('hide')
            },
            error: function() {
                return false
            }
        })
    })
}

function getRowElement(parsedData) {
    let defaultDisplay = ``;
    if (parsedData.DEFAULT_DISPLAY === 1) {
        defaultDisplay =
            `<div class='text-center'>
                <span class='glyphicon glyphicon-ok' aria-hidden='true'></span>
            </div>`
    }

    return `<tr id=`+parsedData.ID_RUBRIQUE+`>
        <td class='tdMove'><span class="glyphicon glyphicon-move" aria-hidden="true"></span></td>
        <td>`
        +parsedData.NOM+
        `</td>
        <td id='default-display'>`
        +defaultDisplay+
        `</td>
        <td id='actions'>
            <div class='text-center'>
                <a href='/formulaire/edit-rubrique/rubrique/`+parsedData.ID_RUBRIQUE+`'>
                    <span title='Modifier' class='glyphicon glyphicon-pencil'></span>
                </a>
                <button data-id='`+parsedData.ID_RUBRIQUE+`' data-nom='`+parsedData.NOM+`' class='btn btn-link delete-rubrique' onclick='return deleteRubrique(this)' data-toggle="modal" data-target="#modal-delete-rubrique">
                    <span title='Supprimer' class='glyphicon glyphicon-trash'></span>
                </button>
            </div>
        </td>
    </tr>`
}