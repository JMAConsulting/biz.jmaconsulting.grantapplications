(function(CRM, $) {

  $(document).ready(function() {
    /**
     * This function is a hack for generating simulated values of "entity_name"
     * in the form-field model.
     *
     * @param {string} field_type
     * @return {string}
     */
    CRM.UF.guessEntityName = function(field_type) {
      switch (field_type) {
        case 'Contact':
        case 'Individual':
        case 'Organization':
        case 'Household':
        case 'Formatting':
          return 'contact_1';
        case 'Activity':
          return 'activity_1';
        case 'Contribution':
          return 'contribution_1';
        case 'Membership':
          return 'membership_1';
        case 'Participant':
          return 'participant_1';
        case 'Case':
          return 'case_1';
        case 'Grant':
          return 'grant_1';
        default:
          if (CRM.contactSubTypes.length && ($.inArray(field_type,CRM.contactSubTypes) > -1)) {
            return 'contact_1';
          }
          else {
            throw "Cannot guess entity name for field_type=" + field_type;
          }
      }
    };
  });

})(CRM, CRM.$);
