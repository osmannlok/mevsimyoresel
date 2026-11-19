<?php
// Heading
$_['heading_title']    = 'Desi/KG Kargo';

// Text
$_['text_extension']   = 'Eklentiler';
$_['text_success']     = 'Başarılı: Desi/KG kargo metodunu başarılı bir şekilde değiştirdiniz!';
$_['text_edit']        = 'Desi/KG Kargoyu Düzenle';
$_['text_none']        = 'Yok';

// Entry
$_['entry_divisor']    = 'Desi Böleni';
$_['entry_desi_rates'] = 'Desi Kademeleri';
$_['entry_kg_rate']    = 'KG Birim Fiyatı (TL/kg)';
$_['entry_max_cost']   = 'Tedarikçi Başına Maksimum Kargo (TL)';
$_['entry_supplier_map'] = 'Tedarikçi Haritası';
$_['entry_tax_class']  = 'Vergi Sınıfı';
$_['entry_status']     = 'Durum';
$_['entry_sort_order'] = 'Sıralama';

// Help
$_['help_divisor']     = 'Örnek: 3000 (cm^3 başına 1 desi)';
$_['help_desi_rates']  = 'Örnek: 2:100,3:150,4:155,5:165,10:195,15:270,20:360,9999:400 - Anlamı: üst desi sınırı:fiyat, ilk uyan kural uygulanır.';
$_['help_kg_rate']     = 'Yuvarlanmış toplam kg x bu fiyat.';
$_['help_max_cost']    = 'Her tedarikçi için kargo tutarı ayrı ayrı hesaplanır ve bu tutarda tavanlanır (örn. 500). Toplam sepet kargosu, tedarikçi kargolarının toplamıdır.';
$_['help_supplier_map'] = 'Format: MODELÖN EKİ:Tedarikçi Adı, MODELÖN EKİ:Tedarikçi Adı. Örnek: TUNA:Kahramanmaraş,HATAY:Hatay,KARSMEH:Kars,YM:Özel Tedarikçi. Ürün modeli ilk “-” işaretine kadar bu ön ek ile eşleştirilir.';
$_['help_calculation'] = 'Her tedarikçi için desi ve kg ayrı ayrı yuvarlanır; yüksek olan fiyat uygulanır ve tedarikçi başına maksimum tutarla tavanlanır. Müşteriye gösterilen kargo, tedarikçi kargolarının toplamıdır. Yuvarlama: ceil.';

// Error
$_['error_permission'] = 'Uyarı: Desi/KG kargo metodunu düzenleme iznine sahip değilsiniz!';
$_['error_rates']      = 'Uyarı: Desi kademeleri geçersiz ya da boş!';
$_['error_divisor']    = 'Uyarı: Desi böleni 0 dan büyük olmalı!';
$_['error_kg_rate']    = 'Uyarı: KG birim fiyatı sayı olmalı!';
$_['error_max_cost']   = 'Uyarı: Tedarikçi başına maksimum kargo tutarı sayı olmalı!';
$_['error_supplier_map'] = 'Uyarı: Tedarikçi haritası geçersiz! Her satırda "ÖN EK:Tedarikçi Adı" biçiminde olmalı ve en az bir kayıt içermeli.';
