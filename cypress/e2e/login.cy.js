describe('Login Page', () => {
  describe('Successful Login', () => {
    it('should login successfully', () => {
      cy.visit('/ensaluti')
      cy.get('input[name="email"]').type('test2@example.com')
      cy.get('input[name="password"]').type('password123')
      cy.get('form').submit()
      cy.url().should('eq', Cypress.config('baseUrl') + '/')
    })
  })

  describe('Login Form Validations', () => {
    it('should display help for blank inputs', () => {
      cy.visit('/ensaluti')
      cy.get('form').submit()
      cy.get('input[name="email"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
      cy.get('input[name="password"] + p.help')
        .should('be.visible')
        .and('contain', 'Kampo deviga')
    })

    it('should display help for malformed email input', () => {
      cy.visit('/ensaluti')
      cy.get('input[name="email"]').type('invalidemail')
      cy.get('form').submit()
      cy.get('input[name="email"] + p.help')
        .should('be.visible')
        .and('contain', 'Nevalida retpoŝtadreso')
    })
  })

  describe('Authentication Errors', () => {
    it('should show error with invalid credentials', () => {
      cy.visit('/ensaluti')
      cy.get('input[name="email"]').type('invalid@example.com')
      cy.get('input[name="password"]').type('wrongpassword')
      cy.get('form').submit()
      cy.get('.message.is-danger')
        .should('be.visible')
        .and('contain', 'Malĝusta retpoŝtadreso aŭ pasvorto')
    })
  })

  describe('Authentication Guards', () => {
    it('unauthenticated-only pages should redirect if authenticated', () => {
      cy.visit('/ensaluti')
      cy.get('input[name="email"]').type('test2@example.com')
      cy.get('input[name="password"]').type('password123')
      cy.get('form').submit()
      cy.visit('/ensaluti')
      cy.url().should('eq', Cypress.config('baseUrl') + '/')
    })

    it('authenticated-only pages should redirect if unauthenticated', () => {
      cy.visit('/elsaluti')
      cy.visit('/konto')
      cy.url().should('include', '/ensaluti')
    })
  })
})