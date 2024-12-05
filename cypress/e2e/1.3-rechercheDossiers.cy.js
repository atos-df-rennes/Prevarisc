describe('Recherche Dossiers', () => {
    beforeEach(() => {
        cy.login('root', 'root')
        cy.visit('/')
    })

    it('Page de recherche des dossiers', () => {
        cy.contains('a.dropdown-toggle', 'Dossiers').click();
        cy.contains('a', 'Rechercher').click();
        cy.contains('Dossiers').should('exist');
    });

   it('Avis favorables', () => {
        cy.contains('a.dropdown-toggle', 'Dossiers').click();
        cy.contains('a', 'Rechercher').click();
        cy.get('input[value="Avis de la commission"]').clear().type('Favorable');
        cy.get('.chosen-drop .chosen-results')
            .contains('Favorable')
            .click();
        cy.get('[name=Rechercher]').click();
        cy.get('.avis.F').should('exist')
        cy.get('.avis.D').should('not.exist')
    });
});
