$(document).ready(function() {
    const typeChampSelect = $('#type_champ')
    
    typeChampSelect.on('change', function() {
        if (typeChampSelect.find(":selected").text() === 'Liste') {
            $('#div-list-value').show()
        } else {
            $('#div-list-value').hide()
        }
    })

    $('#add-champ').on('click', function() {
        const form = this.closest('form')
        const savedFieldsDiv = $('.saved-fields:not(.hidden)')
        const idRubrique = $('#rubrique-id').val()
        const champsDiv = $('.champs')

        const currentUrl = window.location.href
        const isParent = currentUrl.includes('edit-champ')

        const formData = $(form).serialize()
        $.ajax({
            url: '/formulaire/add-champ?isParent='+isParent,
            data: formData+'&rubrique='+idRubrique,
            type: 'POST',
            success: function(data) {
                const jsonParsedData = JSON.parse(data)
                const parsedData = Array.isArray(jsonParsedData) ? jsonParsedData[0] : jsonParsedData

                // On crée la table uniquement si elle n'existe pas
                if (savedFieldsDiv.length === 0) {
                    let titleText = 'Liste des champs'
                    if (isParent) {
                        titleText += ' enfants'
                    }

                    champsDiv.append($('#saved-champs-prototype').clone().removeAttr('id').removeClass('hidden'))
                    $('.champs .panel-title').html(titleText)
                }
                
                const table = $('.saved-fields:not(.hidden) #saved-fields-table tbody')
                table.append(getRowElement(parsedData))

                if (parsedData.TYPE === 'Liste') {
                    const typeRow = table.children().children('#type-'+parsedData.ID_CHAMP)

                    if (parsedData.VALEUR !== null) {
                        typeRow.append(getListElements(jsonParsedData))
                    } else {
                        typeRow.append(`<div class="alert alert-warning">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <strong>Attention&nbsp;!</strong>&nbsp;La liste ne possède aucune valeur, vous devriez en ajouter.
                        </div>`)
                    }
                }
                
                form.reset()
                $('#div-list-value').hide()
            },
            error: function() {
                return false;
            }
        })
        return false
    })

    $('.delete-champ').on('click', function(e) {
        e.preventDefault()
        deleteChamp(this)
    })

    $('#add-list-value').on('click', function(e) {
        e.preventDefault()
        const date = Date.now()
        const html = "<div><input class='form-control' type='text' name='valeur-ajout-"+date+"' id='valeur-ajout-"+date+"'></input><button type='button' class='btn btn-link delete-list-value pull-right'>Retirer</button></div>"

        $('#list-value').append(html)

        $('.delete-list-value').on('click', function(e) {
            e.preventDefault()
            parentDiv = $(this).parent()
            parentDiv.remove()
        })
    })
})

function deleteChamp(element) {
    const idChamp = element.getAttribute('data-id')
    const idRubrique = element.getAttribute('data-rubrique-id')
    const idParent = element.getAttribute('data-id-parent')
    const nom = element.getAttribute('data-nom')

    $("#name-champ").html(nom)
    $("#delete-champ").click(function() {
        const parentDiv = $(element).parent().parent().parent()
        const parentTable = $(element).closest('.saved-fields:not(.hidden)')
        const nbOfRows = parentTable.children('.panel').children('table').children('tbody').children('tr').length

        $.ajax({
            url: '/formulaire/delete-champ/rubrique/'+idRubrique+'/champ/'+idChamp,
            type: 'POST',
            data: idParent,
            success: function() {
                if (nbOfRows === 1) {
                    parentTable.remove()
                } else {
                    parentDiv.remove()
                }

                $("#modal-delete-champ").modal('hide')
            },
            error: function() {
                return false
            }
        })
    })
}

function getRowElement(parsedData) {
    return `<tr>
        <td class="tdMove"><span class="glyphicon glyphicon-move" aria-hidden="true"></span></td>
        <td>`
        +parsedData.NOM+
        `</td>
        <td id='type-`+parsedData.ID_CHAMP+`'>`
        +parsedData.TYPE+
        `</td>
        <td id='actions'>
            <div class='text-center'>
                <a href='/formulaire/edit-champ/rubrique/`+parsedData.ID_RUBRIQUE+`/champ/`+parsedData.ID_CHAMP+`'>
                    <span title='Modifier' class='glyphicon glyphicon-pencil'></span>
                </a>
                <button data-id='`+parsedData.ID_CHAMP+`' data-rubrique-id='`+parsedData.ID_RUBRIQUE+`' data-nom='`+parsedData.NOM+`' class='btn btn-link delete-champ' onclick='return deleteChamp(this)' data-toggle='modal' data-target='#modal-delete-champ'>
                    <span title='Supprimer' class='glyphicon glyphicon-trash'></span>
                </button>
            </div>
        </td>
    </tr>`
}

function getListElements(jsonParsedData) {
    let list = '<ul>'

    for (let i = 0; i < jsonParsedData.length; i++) {
        list += '<li>'+jsonParsedData[i].VALEUR+'</li>'
    }

    list += '</ul>'

    return list
}