<?php

include '../wordpress/wp-content/plugins/gravityforms/export.php';

$forms = GFAPI::get_forms();
$id = null;
foreach ($forms as $form) {
    if ($form['title'] == 'Contact us') {
        if (count($form['fields']) == 0) {
            GFAPI::delete_form( $form['id'] );
            print_r('deleted existing empty form '. $form['id']);
        }
        else {
            $id = $form['id'];
            print_r('[skip] Found Contact us form that wasnt empty: '. $form['id']);
            exit;
        }
    }
}


$forms_json = '{"0":{"title":"Contact us","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"type":"name","id":1,"label":"Name","adminLabel":"","isRequired":true,"size":"medium","errorMessage":"","nameFormat":"advanced","inputs":[{"id":"1.2","label":"Prefix","name":"","choices":[{"text":"Mr.","value":"Mr.","isSelected":false,"price":""},{"text":"Mrs.","value":"Mrs.","isSelected":false,"price":""},{"text":"Miss","value":"Miss","isSelected":false,"price":""},{"text":"Ms.","value":"Ms.","isSelected":false,"price":""},{"text":"Dr.","value":"Dr.","isSelected":false,"price":""},{"text":"Prof.","value":"Prof.","isSelected":false,"price":""},{"text":"Rev.","value":"Rev.","isSelected":false,"price":""}],"isHidden":true,"inputType":"radio"},{"id":"1.3","label":"First","name":""},{"id":"1.4","label":"Middle","name":"","isHidden":true},{"id":"1.6","label":"Last","name":""},{"id":"1.8","label":"Suffix","name":"","isHidden":true}],"formId":2,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","visibility":"visible","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"useRichTextEditor":false,"displayOnly":""},{"type":"email","id":2,"label":"Email","adminLabel":"","isRequired":true,"size":"medium","errorMessage":"","inputs":null,"formId":2,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","visibility":"visible","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","emailConfirmEnabled":false,"multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"useRichTextEditor":false,"displayOnly":""},{"type":"select","id":4,"label":"Subject","adminLabel":"","isRequired":true,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"General Website Feedback","value":"General Website Feedback","isSelected":false,"price":""},{"text":"Website Accessibility","value":"Website Accessibility","isSelected":false,"price":""}],"formId":2,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","visibility":"visible","noDuplicates":false,"defaultValue":"","conditionalLogic":"","productField":"","enablePrice":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"useRichTextEditor":false,"displayOnly":""},{"type":"textarea","id":3,"label":"Message","adminLabel":"","isRequired":true,"size":"medium","errorMessage":"","inputs":null,"formId":2,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","visibility":"visible","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","form_id":"","useRichTextEditor":false,"multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"displayOnly":""}],"version":"2.2.5.14","id":2,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"confirmations":[{"id":"56be333f9c554","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}],"notifications":[{"isActive":true,"id":"56be333f9bb4b","name":"Admin Notification","service":"wordpress","event":"form_submission","to":"notify@proudcity.com","toType":"email","bcc":"","subject":"New submission from {form_title}","message":"{all_fields}","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":false},{"isActive":true,"id":"5a32ef188e0e7","name":"","service":"wordpress","event":"form_submission","to":"2","toType":"field","bcc":"","subject":"Thank you for your feedback","message":"We have received your message and will reply as soon as possible.","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":false}]},"version":"2.2.5.14"}';
$form = GFExport::import_json( $forms_json, $id);
print_R('form saved: ' . $form);


$forms = GFAPI::get_forms();
foreach ($forms as $form) {
    if ($form['title'] == 'Contact us') {
        $id = $form['id'];
    }
}
print_R('found new form: ' . $id);

