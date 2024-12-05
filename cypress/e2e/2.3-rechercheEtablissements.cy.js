describe('Recherche Etablissements', () => {
    beforeEach(() => {
        cy.login('root', 'root')
        cy.visit('/')
    })

    it('Page des recherche des établissements', () => {
        cy.contains('a.dropdown-toggle', 'Établissements').click();
        cy.contains('a', 'Rechercher').click();
        cy.contains('Etablissements').should('exist');
    });

    it('Etablissement avec avis défavorable', () => {
        cy.contains('a.dropdown-toggle', 'Établissements').click();
        cy.contains('a', 'Rechercher').click();
        cy.get('[class="ui-multiselect ui-widget ui-state-default ui-corner-all"]').eq(2).click();
        cy.get('#ui-multiselect-6-option-1').then(($checkbox) => {
            if (!$checkbox.is(':checked')) {
                cy.wrap($checkbox).check();
            }
        });
        cy.get('[name=Rechercher]').click();
        cy.get('.avis.F').should('not.exist')
        cy.get('.avis.D').should('exist')
    });

    it('Etablissement avec avis favorable', () => {
        cy.contains('a.dropdown-toggle', 'Établissements').click();
        cy.contains('a', 'Rechercher').click();
        cy.get('[class="ui-multiselect ui-widget ui-state-default ui-corner-all"]').eq(2).click();
        cy.get('#ui-multiselect-6-option-0').then(($checkbox) => {
            if (!$checkbox.is(':checked')) {
                cy.wrap($checkbox).check();
            }
        });
        cy.get('[name=Rechercher]').click();
        cy.get('.avis.F').should('exist')
        cy.get('.avis.D').should('not.exist')
    });
});
