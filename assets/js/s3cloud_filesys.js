jQuery(document).ready(function($) {
    // Função para lidar com o clique no botão 's3cloud_choose_bucket'
    jQuery('*').on('click', function(event) {
        var target = jQuery(event.target);
        var buttonclicked = target.closest("button").attr('id');
        if (buttonclicked === 's3cloud_choose_bucket') {
            if (event.isDefaultPrevented()) return;
            event.preventDefault();
            var s3cloud_bucket_name = jQuery("#select_bucket").val();
            jQuery('.s3cloud_transfer_row').show();
            var s3cloud_temp = "<div class='spinner-border' style='width: 34px; height: 34px;' role='status'><span class='sr-only'></span></div>";
            jQuery('#s3cloud_treeview2').html(s3cloud_temp);
            jQuery("#s3cloud_bucket_selected").text(s3cloud_bucket_name);
            jQuery("#s3cloud_bucket_name").text(s3cloud_bucket_name);
            var onTreeNodeSelected2 = function(e, node) {
                jQuery("#s3cloud_selected_cloud").text(node["text"]);
                jQuery("#s3cloud_server_folder_modal").text(node["text"]);
                jQuery('#s3cloud_treeview2').treeview('collapseAll', {
                    silent: true
                });
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    'action': 's3cloud_ajax_create_filesys_cloud',
                    'bucket_name': s3cloud_bucket_name
                },
                dataType: 'json',
                success: function(data_s3cloud_filesys) {
                    jQuery('#s3cloud_treeview2').treeview({
                        data: data_s3cloud_filesys,
                        expandIcon: "bi bi-node-plus",
                        collapseIcon: "bi bi-node-minus",
                        emptyIcon: "bi bi-folder",
                        onNodeSelected: onTreeNodeSelected2
                    });
                    jQuery('#s3cloud_treeview2').treeview('collapseAll', {
                        silent: true
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log("Ajax Error: " + errorThrown);
                    console.log('request failed');
                }
            });
        }
    });

    // Função para lidar com a seleção de nós na árvore 'treeview'
    var onTreeNodeSelected = function(e, node) {
        jQuery("#s3cloud_server_folder_label").text("Folder Server: ");
        jQuery("#s3cloud_selected").text(node["text"]);
        jQuery('#treeview').treeview('collapseAll', {
            silent: true
        });
    };

    // Requisição AJAX para obter a estrutura de diretórios
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            'action': 's3cloud_ajax_create_filesys'
        },
        dataType: 'json',
        success: function(data_s3cloud_filesys) {
            // Inicializa a treeview com todos os níveis de nós
            jQuery('#treeview').treeview({
                data: data_s3cloud_filesys,
                expandIcon: "bi bi-node-plus",
                collapseIcon: "bi bi-node-minus",
                emptyIcon: "bi bi-folder",
                onNodeSelected: onTreeNodeSelected
            });
            jQuery('#treeview').treeview('collapseAll', {
                silent: true
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Ajax Error: " + errorThrown);
            console.log('request failed');
        }
    });
});
