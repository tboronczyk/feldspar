const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: "http://nginx",
    specPattern: [
      'cypress/e2e/signup.cy.js',
      'cypress/e2e/login.cy.js',
      'cypress/e2e/forgot-password.cy.js',
      //'cypress/e2e/**/*.cy.js'
    ],
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
    supportFile: false
  },
});
