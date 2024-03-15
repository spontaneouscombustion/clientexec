<?php

class CE_View_Helper_PriceAdaptiveSize extends Zend_View_Helper_Abstract
{
    public function priceAdaptiveSize($package, $user)
    {

        $termId = $package['pricing'][$package['selectedTermIndex']]['termId'];
        $term = $package['pricing'][$package['selectedTermIndex']]['term'];

        if ($termId != 0) {
            $term = 'Every ' . $term;
        }

        $priceClass = '';

        $billingGateway = new BillingGateway($user);
        $term = $billingGateway->translateText($term, $user);
        $priceFormatted = $package['pricing'][$package['selectedTermIndex']]['price'];
        $priceFormattedTextSize = strlen($priceFormatted);

        if ($priceFormattedTextSize >= 10 && $priceFormattedTextSize < 12) {
            $priceClass = 'pricesMedium';
        } elseif ($priceFormattedTextSize >= 12 && $priceFormattedTextSize < 14) {
            $priceClass = 'pricesSmall';
        } elseif ($priceFormattedTextSize >= 14 && $priceFormattedTextSize < 16) {
            $priceClass = 'pricesXSmall';
        } elseif ($priceFormattedTextSize >= 16) {
            $priceClass = 'pricesXXSmall';
        }


        return <<<EOD

            <div class='$priceClass'>$priceFormatted</div> <p><span class='compare-term'>$term</span></p>
EOD;
    }
}
