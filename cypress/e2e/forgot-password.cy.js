describe('Forgot Password Flow', () => {
  const baseUrl = Cypress.config('baseUrl')
  const testEmail = 'test2@example.com'
  const newPassword = 'NewSecurePassword123'
  const mailhogApiUrl = 'http://mailhog:8025/api/v2/messages'

  it('should reset the password and log in with the new password', () => {
    // Navigate to password reset request page
    cy.visit(`${baseUrl}/pasvorto/forgesita`)

    // Test form behavior
    cy.get('form').submit();
    cy.get('input[name="email"] + p.help')
      .should('be.visible')
      .and('contain', 'Kampo deviga')

    cy.get('input[name=email]').type('invalidemail')
    cy.get('form').submit();
    cy.get('input[name="email"] + p.help')
      .should('be.visible')
      .and('contain', 'Nevalida retpoŝtadreso')

    // Enter non-matching password and submit
    cy.get('input[name=email]').clear().type('noexist@example.com')
    cy.get('form').submit()

    // Ensure redirection to confirmation page
    cy.url().should('eq', `${baseUrl}/pasvorto/sendita`);

    // Navigate back to password reset request page
    cy.visit(`${baseUrl}/pasvorto/forgesita`)

    // Enter proper email and submit form
    cy.get('input[name=email]').clear().type(testEmail)
    cy.get('form').submit()
    cy.url().should('eq', `${baseUrl}/pasvorto/sendita`);
    
    // Execute worker to send email
    cy.exec('php bin/worker');

    // Fetch email content via Mailhog API
    cy.request(mailhogApiUrl)
      .its('body.items')
      .should('have.length.greaterThan', 0) // Ensure an email exists
      .then((emails) => {
        const latestEmail = emails[0]; // Get most recent email
        let emailBody = latestEmail.Content.Body;

        // Decode MIME Quoted-Printable encoding
        emailBody = emailBody.replace(/=\r?\n/g, ''); // Remove soft line breaks
        emailBody = emailBody.replace(/=([0-9A-F]{2})/g, (match, p1) => String.fromCharCode(parseInt(p1, 16)));

        // Debugging: Print email body to console
        //cy.log('EMAIL BODY:', emailBody);
        //console.log('EMAIL BODY:', emailBody);

        // Capture full reset link with JWT token
        const resetLinkRegex = /(https?:\/\/[a-zA-Z0-9.-]+\/pasvorto\/restarigi\/[a-zA-Z0-9._-]+)/;
        const match = emailBody.match(resetLinkRegex);

        // Ensure we found a valid reset link
        expect(match, 'Reset link should be found in email').to.not.be.null;

        const resetLink = match[0].replace('https://volontulo.net', baseUrl);

        // Ensure malformed link redirects to reset request page
        cy.visit(resetLink.substring(0, resetLink.length - 5));
        cy.url().should('eq', `${baseUrl}/pasvorto/forgesita`);
        
        // Visit reset link
        cy.visit(resetLink);

        // Test form behavior
        cy.get('form').submit();
        cy.get('input[name="password"] + p.help')
          .should('be.visible')
          .and('contain', 'Kampo deviga')
        cy.get('input[name="confirmPassword"] + p.help')
          .should('be.visible')
          .and('contain', 'Kampo deviga')

        cy.get('input[name=password]').type(newPassword);
        cy.get('input[name=confirmPassword]').type(newPassword + '123');
        cy.get('form').submit();
        cy.get('input[name="confirmPassword"] + p.help')
          .should('be.visible')
          .and('contain', 'Ripetita pasvorto ne kongruas kun nova pasvorto')

        // Enter new password and confirm
        cy.get('input[name=password]').clear().type(newPassword);
        cy.get('input[name=confirmPassword]').clear().type(newPassword);
        cy.get('form').submit();

        // Ensure redirection to login page
        cy.url().should('eq', `${baseUrl}/ensaluti`);

        // Attempt to log in with new password
        cy.get('input[name=email]').type(testEmail);
        cy.get('input[name=password]').type(newPassword);
        cy.get('form').submit();
        cy.url().should('eq', `${baseUrl}/`);
      
        // Log out
        cy.visit(`${baseUrl}/elsaluti`);

        // Ensure reset link is expired
        cy.visit(resetLink);
        cy.url().should('eq', `${baseUrl}/pasvorto/forgesita`);
      });
  });
});
