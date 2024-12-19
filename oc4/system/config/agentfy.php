<?php 
$_['module_agentfy_setting'] = array(
	'api_key' => '',
    "product_template" => "# %title%\r\n%description%\r\n\r\n### Attributes\r\n* Model: %model%\r\n* Image URL: %image%\r\n* Quantity: %quantity%\r\n* Product page URL: %seoUrl%\r\n* Price: %price%\r\n* Category: %categories%\r\n* Manufacturer: %manufacturer%\r\n%attributes%",
    'category_template' => "# %title%\r\n%description%\r\n\r\n### Attributes\r\n* Category page URL: %seoUrl%",
    'manufacturer_template' => "# %title%\r\n### Attributes\r\n* Manufacturer page URL: %seoUrl%"
);

$_['module_agentfy_display'] = array(
	'button' => array(
        "title"=> "Support",
        "icon"=> "",
        "animation" => "ping",
        "color"=> "black",
        "size"=> "md",
        "type"=> "rounded",
        "shadow"=> "none",
        "position" => array(
            "bottom" => "15px",
             "right" => "15px"
        )
    )
);
?>