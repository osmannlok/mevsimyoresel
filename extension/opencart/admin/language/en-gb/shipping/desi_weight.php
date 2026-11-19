<?php
// Heading
$_['heading_title']    = 'Desi/KG Shipping';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Success: You have successfully modified the Desi/KG shipping method!';
$_['text_edit']        = 'Edit Desi/KG Shipping';
$_['text_none']        = 'None';

// Entry
$_['entry_divisor']    = 'Desi Divisor';
$_['entry_desi_rates'] = 'Desi Tier Rates';
$_['entry_kg_rate']    = 'KG Unit Rate (TL/kg)';
$_['entry_max_cost']   = 'Maximum Shipping Per Supplier (TL)';
$_['entry_supplier_map'] = 'Supplier Map';
$_['entry_tax_class']  = 'Tax Class';
$_['entry_status']     = 'Status';
$_['entry_sort_order'] = 'Sort Order';

// Help
$_['help_divisor']     = 'Example: 3000 (cm^3 per 1 desi)';
$_['help_desi_rates']  = 'Example: 2:100,3:150,4:155,5:165,10:195,15:270,20:360,9999:400 - Meaning: max desi bound:cost, first matching rule applies.';
$_['help_kg_rate']     = 'Rounded total kg x this rate.';
$_['help_max_cost']    = 'Shipping is calculated and capped separately for each supplier (e.g. 500). The order shipping fee is the sum of the supplier fees.';
$_['help_supplier_map'] = 'Format: MODEL PREFIX:Supplier Name, MODEL PREFIX:Supplier Name. Example: TUNA:Kahramanmaraş,HATAY:Hatay,KARSMEH:Kars,YM:Özel Tedarikçi. The product model is matched up to the first “-” character.';
$_['help_calculation'] = 'Desi and kg are rounded separately per supplier; the higher cost applies and is capped per supplier. The shipping fee shown to the customer is the sum of the supplier fees. Rounding: ceil.';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify the Desi/KG shipping method!';
$_['error_rates']      = 'Warning: Desi tier rates are invalid or empty!';
$_['error_divisor']    = 'Warning: Desi divisor must be greater than 0!';
$_['error_kg_rate']    = 'Warning: KG unit rate must be numeric!';
$_['error_max_cost']   = 'Warning: The maximum shipping per supplier must be numeric!';
$_['error_supplier_map'] = 'Warning: The supplier map is invalid! Each entry must be "PREFIX:Supplier Name" and at least one entry is required.';
