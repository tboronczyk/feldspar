describe('Signup Page', () => {
  describe('Successful Signup', () => {
    it('should navigate through email signup process', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('input[name="username"]').type('testuser1')
      cy.get('input[name="email"]').type('test1@example.com')
      cy.get('input[name="password"]').type('password123')
      cy.get('input[name="confirmPassword"]').type('password123')
      cy.get('form').submit()
      cy.url().should('eq', Cypress.config('baseUrl') + '/')
    })
  })

  describe('Signup Form Validations', () => {
    it('should display help for blank inputs', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('form').submit()
      cy.get('input[name="username"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
      cy.get('input[name="email"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
      cy.get('input[name="password"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
      cy.get('input[name="confirmPassword"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
    })

    it('should display help for invalid email', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('input[name="email"]').type('invalid@example')
      cy.get('form').submit()
      cy.get('input[name="email"] + p.help')
        .should('be.visible')
        .and('contain', 'Nevalida retpoŝtadreso')
    })

    it('should display help if confirm password does not match', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('input[name="password"]').type('password')
      cy.get('input[name="confirmPassword"]').type('password123')
      cy.get('form').submit()
      cy.get('input[name="confirmPassword"] + p.help')
        .should('be.visible')
        .and('contain', 'Ripetita pasvorto ne kongruas kun pasvorto')
    })
  })

  describe('Signup Restrictions', () => {
    it('should display help if username is already registered', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('input[name="username"]').type('testuser')
      cy.get('form').submit()
      cy.get('input[name="username"] + p.help')
        .should('be.visible')
        .and('contain', 'Uzantnomo jam uzita')
    })

    it('should display help if email is already registered', () => {
      cy.visit('/registrigxi/retposxto')
      cy.get('input[name="email"]').type('test1@example.com')
      cy.get('form').submit()
      cy.get('input[name="email"] + p.help')
        .should('be.visible')
        .and('contain', 'Retpoŝtadreso jam uzita')
    })
  })
})