Feature: Pages
  In order to verify the website is working
  As different users
  I need to be able to view some pages

@javascript
Scenario: Open the homepage
  Given I am at "/"
  Then I should see "DrupalStand"

@javascript @api
Scenario: Open the user page
  Given I am logged in as a user with the "authenticated" role
  When I am at "/user"
  Then I should see "Member for"
