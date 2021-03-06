(function($){

    $("#{$swfuId|noescape}").swfupload({
            flash_url : {=\Nette\Environment::expand("{$interface->baseUrl}/swf/swfupload.swf")|escapeJS|noescape},
            flash9_url : {=\Nette\Environment::expand("{$interface->baseUrl}/swf/swfupload_fp9.swf")|escapeJS|noescape},
            upload_url: {$backLink|escapeJS|noescape},
            post_params: {
                token : {$token|escapeJS|noescape},
                sender: "MFU-Swfupload"
            },

            file_size_limit : {$sizeLimit|escapeJS|noescape},
            file_types : "*.*",
            file_types_description : "All Files",
            file_upload_limit : {$maxFiles|escapeJS|noescape},

            custom_settings : {
                    progressTarget : "{$swfuId|noescape}progress",
                    cancelButtonId : "{$swfuId|noescape}btnCancel"
            },
            debug: false,

            // Button settings
            button_image_url: {=\Nette\Environment::expand("{$interface->baseUrl}/imgs/XPButtonUploadText_89x88.png")|escapeJS|noescape},
            button_width: "89",
            button_height: "22",
            button_placeholder_id : "{$swfuId|noescape}placeHolder",
    });

    return true;

})(jQuery);