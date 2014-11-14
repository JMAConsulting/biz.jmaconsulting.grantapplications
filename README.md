Grant Application Pages Extension
=================================

This extension to CiviCRM allows members of the public to apply for Grants, either themselves or as individuals on behalf of organizations. Applications can be saved in draft form repeatedly before being submitted days or weeks later, which helps with complex submissions.

Administrators can create and administer Grant Application Pages in a manner similar to Contribution Pages. Custom Profiles for Grants and Contacts, including core and custom fields defined for grants and contacts, can be added to Grant Application Pages. Shortcode support is provided for adding these pages to WordPress posts.

Installation
------------

1. As part of your general CiviCRM installation, you should set a CiviCRM Extensions Directory at Administer >> System Settings >> Directories.
2. As part of your general CiviCRM installation, you should set an Extension Resource URL at Administer >> System Settings >> Resource URLs.
3. Navigate to Administer >> System Settings >> Manage Extensions.
4. Beside Grant Application Pages click Install.

General Configuration
---------------------

1. Create and administer needed Grant Types at Administer >> CiviGrant >> Grant Types.
2. Create any custom fields needed on your grant application pages or to administer your grants at Administer >> Custom Data and Profiles >> Custom Fields, making sure to indicate that they are used for Grants.
3. Create a profile or two for the fields needed on a Grant Application Page at Administer >> Custom Data and Profiles >> Profiles.
4. *Advanced Configuration* If you have Smarty templating and html coding skills, it is possible to modify the portion of the receipt email that is common to all Grant Application Pages. Nagivate to Administer >> Communications >> Message Templates, click on the System Workdlow Messages tab, and click Edit beside Grants - Receipt (on-line). It is not uncommon for inexperienced users to cause problems by editing these message templates. If you do, you can go back to the default message easily by navigating to Administer >> Communications >> Message Templates, clicking on the System Workdlow Messages tab, and click Revert to default beside Grants - Receipt (on-line). 
 

Configure a Grant Application Page
----------------------------------

1. Navigate to Grants >> New Grant Applications Page.
2. Provide a Title for the page, select the grant type the applications will be for, fill in any other information desired, and click Continue >>. You will generally have one Grant Application Page for each grant type that users can apply for online.
3. Complete the Receipts configuration page, and click Save and Next.
4. Select a profile for the top and / or bottom of the application page. A suggested practice is to use one profile for contact information that is common to other parts of your CiviCRM installation, and a second profile containing fields specific to the Grant Application process. Click Save and Done.
5. From the CiviGrant Dashboard (Grants >> Dashboard), you can use the Manage Grant Application Pages section to Configure existing Grant Application Pages, go to their live pages, or disable or delete them. 
6. For WordPress installations, Grant Application Pages are available to be inserted into posts as shortcodes in the same manner as other CiviCRM objects. When creating a new post or editting an existing one, click the CiviCRM icon above the post's textarea, select Grant Application Page, select the desired grant application page from the select list that appears, and click the Insert Form button. Text similar to `[civicrm component="grant" id="2" mode="live"]` will appear in the textarea. Once you have completed the rest of your post, click Publish to create a new post or Update to save your changed post.
   a. In order to enable shortcode support for WordPress installations, please replace the civicrm.php and the civicrm.js files present under the shortcode-wp directory, with the ones that come with the default CiviCRM installation. They can be found under wp-content/plugins/civicrm in most cases.
7. Click View Post to view a new grant application page to ensure that it has all of the fields desired. Complete and submit it to ensure that it is working properly.
8. Review the email receipt that was sent, if that option was selected. To modify it, go to Grants >> Dashboard, and beside the appropriate Grant Application Page, click Configure >> Receipts, then edit the fields at the bottom of the page.
