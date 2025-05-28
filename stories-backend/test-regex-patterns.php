<?php
/**
 * Test Regex Patterns Against Actual Amazon HTML
 */

echo "<h2>Testing Regex Patterns Against Amazon HTML</h2>\n";

// The actual HTML you provided
$amazonHtml = '<div id="formats" class="a-section a-spacing-none MMGridLayout">       <div id="tmmSwatches" class="a-section a-spacing-none nonJSFormats"> <ul id="tmmSwatchesList" class="a-unordered-list a-nostyle a-vertical">                        <div class="a-row formatsRow a-ws-row" role="presentation">                         <div id="tmm-grid-swatch-KINDLE" class="a-column a-span6 a-text-left swatchElement unselected celwidget" role="listitem" data-csa-c-id="k81rbx-gewdah-ips49q-kyalpj" data-cel-widget="tmm-grid-swatch-KINDLE">                          <span class="a-button a-spacing-none a-button-toggle format" id="a-autoid-0"><span class="a-button-inner"><a href="/Coraline-Neil-Gaiman-ebook/dp/B0037B6Q66/ref=tmm_kin_swatch_0" role="radio" aria-checked="false" aria-current="" class="a-button-text a-text-left" id="a-autoid-0-announce">      <span class="slot-title">
                  <span aria-label="Kindle Edition Format:">Kindle Edition</span> <br>  </span>
               <span class="slot-price">
                                                   <span aria-label="£4.53" class="a-size-base a-color-secondary"> £4.53 </span>        </span>
                                        <br id="sw-slots-grid-line-break">

              <span class="slot-extraMessage">
                                                                                        <span class="kindleExtraMessage">                <div class="a-section"> <span aria-label="Available instantly" class="a-size-small a-text-normal"> Available instantly </span> </div>  </span> </span>
              </a></span></span>  </div>                                 <div id="tmm-grid-swatch-HARDCOVER" class="a-column a-span6 a-text-left swatchElement selected celwidget a-span-last a-ws-span-last" role="listitem" data-csa-c-id="kiyhs3-msztr3-19n0ld-1h7hrb" data-cel-widget="tmm-grid-swatch-HARDCOVER">                       <span class="a-button a-button-selected a-spacing-none a-button-toggle format" id="a-autoid-1"><span class="a-button-inner"><a href="javascript:void(0)" role="radio" aria-checked="true" aria-current="page" class="a-button-text a-text-left" id="a-autoid-1-announce">      <span class="slot-title">
                  <span aria-label="Hardcover Format:">Hardcover</span> <br>  </span>
               <span class="slot-price">
                                                 <span aria-label="£13.70" class="a-size-base a-color-price a-color-price"> £13.70 </span>        </span>
                                 <br id="sw-slots-grid-line-break">

                               </a></span></span>  </div>       </div>    <div class="a-row formatsRow a-ws-row" role="presentation">                         <div id="tmm-grid-swatch-PAPERBACK" class="a-column a-span6 a-text-left swatchElement unselected celwidget" role="listitem" data-csa-c-id="jut6vg-ge6x9w-j367w0-gtuhs8" data-cel-widget="tmm-grid-swatch-PAPERBACK">                       <span class="a-button a-spacing-none a-button-toggle format" id="a-autoid-2"><span class="a-button-inner"><a href="/Coraline-Neil-Gaiman/dp/0747562105/ref=tmm_pap_swatch_0" role="radio" aria-checked="false" aria-current="" class="a-button-text a-text-left" id="a-autoid-2-announce">      <span class="slot-title">
                  <span aria-label="Paperback Format:">Paperback</span> <br>  </span>
               <span class="slot-price">
                                                 <span aria-label="£7.89" class="a-size-base a-color-secondary"> £7.89 </span>        </span>
                                 <br id="sw-slots-grid-line-break">

                               </a></span></span>  </div>                              <div id="tmm-grid-swatch-AUDIOBOOK" class="a-column a-span6 a-text-left swatchElement unselected celwidget a-span-last a-ws-span-last" role="listitem" data-csa-c-id="ppsiie-geukwb-1as37f-qfrmpv" data-cel-widget="tmm-grid-swatch-AUDIOBOOK">                       <span class="a-button a-spacing-none a-button-toggle format" id="a-autoid-3"><span class="a-button-inner"><a href="/Coraline-Neil-Gaiman/dp/006051048X/ref=tmm_abk_swatch_0" role="radio" aria-checked="false" aria-current="" class="a-button-text a-text-left" id="a-autoid-3-announce">      <span class="slot-title">
                  <span aria-label="Audio CD Format:">Audio CD</span> <br>  </span>
               <span class="slot-price">
                                                 <span aria-label="£18.91" class="a-size-base a-color-secondary"> £18.91 </span>        </span>
                                 <br id="sw-slots-grid-line-break">

                               </a></span></span>  </div>       </div>          </ul>  </div>          </div>';

echo "<h3>1. Testing Selected Format Detection (New Method)</h3>\n";

// Test each format individually to see which one is selected
$formatChecks = [
    'HARDCOVER' => 'Hardcover',
    'PAPERBACK' => 'Paperback',
    'KINDLE' => 'Kindle',
    'AUDIOBOOK' => 'Audio CD'
];

$selectedFormat = null;
$selectedPrice = null;

foreach ($formatChecks as $formatKey => $formatName) {
    $pattern = '/id="tmm-grid-swatch-' . $formatKey . '"[^>]*class="[^"]*selected[^"]*".*?href="javascript:void\(0\)".*?aria-label="£(\d+\.\d{2})"/is';

    if (preg_match($pattern, $amazonHtml, $selectedMatch)) {
        $selectedFormat = $formatName;
        $selectedPrice = $selectedMatch[1];
        echo "<p><strong>✅ Selected format found:</strong> {$formatName} at £{$selectedPrice}</p>\n";
        break;
    } else {
        echo "<p>❌ {$formatName} not selected</p>\n";
    }
}

if (!$selectedFormat) {
    echo "<p><strong>❌ No selected format found with new method</strong></p>\n";

    // Debug: Check what we can find
    echo "<h4>Debug: Testing individual components</h4>\n";

    if (preg_match('/id="tmm-grid-swatch-HARDCOVER"[^>]*class="[^"]*selected[^"]*"/is', $amazonHtml)) {
        echo "<p>✓ Found HARDCOVER with selected class</p>\n";
    }

    if (preg_match('/href="javascript:void\(0\)"/is', $amazonHtml)) {
        echo "<p>✓ Found javascript:void(0) href</p>\n";
    }

    if (preg_match('/aria-label="£13\.70"/is', $amazonHtml)) {
        echo "<p>✓ Found £13.70 price</p>\n";
    }
}

echo "<h3>2. Testing Individual Format Patterns</h3>\n";

$patterns = [
    'Hardcover' => '/id="tmm-grid-swatch-HARDCOVER".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Paperback' => '/id="tmm-grid-swatch-PAPERBACK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Kindle' => '/id="tmm-grid-swatch-KINDLE".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
    'Audio CD' => '/id="tmm-grid-swatch-AUDIOBOOK".*?<a href="([^"]*)".*?aria-label="£(\d+\.\d{2})"/is',
];

foreach ($patterns as $format => $pattern) {
    if (preg_match($pattern, $amazonHtml, $matches)) {
        echo "<p><strong>✅ {$format}:</strong> £{$matches[2]} - {$matches[1]}</p>\n";
    } else {
        echo "<p><strong>❌ {$format}:</strong> Not found</p>\n";
    }
}

echo "<h3>3. Raw Pattern Matches</h3>\n";
echo "<p>Looking for all tmm-grid-swatch elements:</p>\n";

if (preg_match_all('/id="tmm-grid-swatch-(\w+)"/', $amazonHtml, $allMatches)) {
    echo "<p>Found formats: " . implode(', ', $allMatches[1]) . "</p>\n";
} else {
    echo "<p>No tmm-grid-swatch elements found</p>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed. This shows how the regex patterns perform against the actual Amazon HTML.</em></p>\n";
