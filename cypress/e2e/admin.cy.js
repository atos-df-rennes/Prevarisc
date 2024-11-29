describe("Page d'administration", () => {
    beforeEach(() => {
        cy.login('root', 'root')
        cy.visit('/')

        const moreNavButton = cy.get('ul.nav').first().children('li').last()
        moreNavButton.click()
        cy.contains('a', 'Administration').click()
    })

    it("Affiche la page d'informations générales", () => {
        cy.contains('Informations générales').should('be.visible')
        cy.contains('Utilisateurs connectés').should('be.visible')
    })

    // @todo: Page de gestion des éléments supprimés

    it('Affiche la page de gestion des utilisateurs', () => {
        cy.contains('a', 'Gestion des utilisateurs').click()

        // Vérification des boutons d'actions
        cy.contains('Général').should('be.visible')
        cy.contains('Ajouter un utilisateur').should('be.visible')
        cy.contains('Créer un groupe').should('be.visible')
        cy.contains('Gérer les droits des groupes').should('be.visible')
        
        // Vérification de la liste principale
        cy.contains('Utilisateurs actifs').should('be.visible')

        // Action d'ajout d'un utilisateur
        cy.contains('a', 'Ajouter un utilisateur').click()
        cy.contains("Ajout d'un utilisateur").should('be.visible')

        // Action de création d'un groupe
        cy.contains('a', 'Créer un groupe').click()
        cy.contains("Ajout d'un groupe").should('be.visible')

        // Action de gestion des droits des groupes
        cy.contains('a', 'Gérer les droits des groupes').click()
        cy.contains('Matrice des droits').should('be.visible')

    })

    it('Affiche la page de gestion des communes', () => {
        cy.contains('a', 'Gestion des communes').click()

        cy.contains('Informations sur la commune').should('be.visible')
    })

    it('Affiche la page de fusion des communes', () => {
        cy.contains('a', 'Fusion des communes').click()

        cy.contains('Fusion des communes').should('be.visible')
    })

    it('Affiche la page de gestion des couches cartographiques', () => {
        cy.contains('a', 'Gestion des couches cartographiques').click()

        // Vérification des boutons d'actions
        cy.contains('Ajouter une couche personnalisée').should('be.visible')
        cy.contains('Ajouter une couche IGN').should('be.visible')
        cy.contains("Changer l'ordre des couches").should('be.visible')

        // Action d'ajout d'une couche personnalisée
        cy.contains('a', 'Ajouter une couche personnalisée').click()
        cy.contains("Ajout d'une couche cartographique").should('be.visible')

        // Action d'ajout d'une couche IGN
        cy.contains('a', 'Revenir sur la liste des couches cartographiques').click()
        cy.contains('a', 'Ajouter une couche IGN').click()
        cy.contains("Ajout d'une couche cartographique IGN").should('be.visible')
    })

    it('Affiche la page de gestion des commissions', () => {
        cy.contains('a', 'Gestion des commissions').click()

        cy.contains('Champ de compétence de la commission').should('be.visible')

        cy.contains('a', 'Commission communale').click()
        cy.contains('Aucun résultat.').should('be.visible')

        cy.contains('a', 'Commission intercommunale').click()
        cy.contains('Aucun résultat.').should('be.visible')

        cy.contains('a', "Commission d'arrondissement").click()
        cy.contains('Champ de compétence de la commission').should('be.visible')

        cy.contains('a', 'Divers').click()
        cy.contains('Aucun résultat.').should('be.visible')

        cy.contains('button', 'Gestion des commissions').should('be.visible')
        cy.contains('button', 'Gestion des commissions').click()
        cy.contains('Ajouter une commission').should('be.visible')
    })

    it('Affiche la page de gestion des documents', () => {
        cy.contains('a', 'Gestion des documents').click()

        cy.contains('Visible après vérrouillage').should('be.visible')

        cy.contains('a', 'Ajouter un document').should('be.visible')
        cy.contains('a', 'Ajouter un document').click()
        cy.contains('Ajouter un document type').should('be.visible')
    })

    // @todo: Page de gestion des prescriptions
    // @todo: Page de gestion des textes applicables

    it('Affiche la page du tableau des périodicités', () => {
        cy.contains('a', 'Tableau des périodicités').click()

        cy.contains('ERP').should('be.visible')
        cy.contains('IGH').should('be.visible')
    })

    it('Affiche la page des groupements de communes', () => {
        cy.contains('a', 'Groupements de communes').click()

        cy.contains('Général').should('be.visible')

        cy.contains('a', 'Créer un nouveau groupement').should('be.visible')
        cy.contains('a', 'Créer un nouveau groupement').click()
        cy.contains("Création d'un nouveau groupement").should('be.visible')
    })

    it('Affiche la page des messages des alertes', () => {
        cy.contains('a', 'Messages des alertes').click()

        cy.contains('Message de changement de statut').should('be.visible')
        cy.contains("Message de changement d'avis").should('be.visible')
        cy.contains('Message de changement de classement').should('be.visible')

        cy.contains('Voir les balises disponibles').should('be.visible')
        cy.contains('Voir les balises disponibles').click()
        cy.contains('Liste des balises disponibles').should('be.visible')
    })

    it('Affiche la page des formulaires', () => {
        cy.contains('a', 'Formulaires').click()

        cy.contains("Descriptif de l'établissement").should('be.visible')
        cy.contains("Effectifs & Dégagements de l'établissement").should('be.visible')
        cy.contains('Vérifications techniques du dossier').should('be.visible')
        cy.contains('Effectifs & Dégagements du dossier').should('be.visible')
    })
})