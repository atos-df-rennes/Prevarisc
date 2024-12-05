describe("Page d'accueil", () => {
    beforeEach(() => {
        cy.login('root', 'root')
        cy.visit('/')
    })

    it("Ouvre le bloc Plat'AU", () => {
        const blocPlatau = cy.contains("Dossiers Plat'AU à traiter")
        const chevron = blocPlatau.find('i')

        chevron.click()
        
        const resultList = cy.get('#dossierPlatau').find('ul')
        resultList.find('li').should('not.be.empty')
    })
})
