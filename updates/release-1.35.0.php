<?php
$form = GFAPI::get_form( 1 );

foreach ($form['fields'] as $i => $field) {
    if ($field->type == 'email') {
        $form['fields'][$i]->labelPlacement = 'hidden_label';
        $form['fields'][$i]->label = 'Enter email';
    }
}
$result = GFAPI::update_form( $form );
print_r('Updating Newsletter signup form. Result: ');
print_r($result);