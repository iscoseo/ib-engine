(function($) {
    'use strict';

    $(document).ready(function() {
        var $editables = $('[data-ib-editable]');
        if ($editables.length === 0) return;

        // Crear la barra flotante con separador antes del Reset
        var $saveBar = $('<div id="ib-editor-bar">' +
                            '<span class="ib-status">Editor</span>' +
                            '<button id="ib-lock-btn" title="Activar edición">🔒</button>' +
                            '<button id="ib-save-btn">Guardar</button>' +
                            '<button id="ib-download-btn">Descargar</button>' +
                            '<button id="ib-undo-btn" title="Deshacer último guardado">Deshacer</button>' +
                            '<div class="ib-divider"></div>' +
                            '<button id="ib-reset-btn" title="Restablecer a valores de archivo">Reset All</button>' +
                         '</div>').appendTo('body');

        // Tooltip flotante para reset por elemento
        var $resetTip = $('<div id="ib-key-reset-tip">↺ Reset este elemento</div>').appendTo('body');
        var $currentEditable = null;

        var $saveBtn = $('#ib-save-btn');
        var $resetBtn = $('#ib-reset-btn');
        var $downloadBtn = $('#ib-download-btn');
        var $lockBtn = $('#ib-lock-btn');
        var $undoBtn = $('#ib-undo-btn');
        var isLocked = true;

        // Bloquear edición por defecto
        $editables.attr('contenteditable', 'false').addClass('ib-locked');

        $lockBtn.on('click', function() {
            isLocked = !isLocked;
            if (isLocked) {
                $editables.attr('contenteditable', 'false').addClass('ib-locked');
                $lockBtn.html('🔒').attr('title', 'Activar edición');
                hideResetTip();
            } else {
                $editables.attr('contenteditable', 'true').removeClass('ib-locked');
                $lockBtn.html('🔓').attr('title', 'Bloquear edición');
            }
        });

        function getPageContent() {
            var content = {};
            $editables.each(function() {
                var key = $(this).data('ib-editable');
                var val = $(this).html();
                content[key] = val;
            });
            return content;
        }

        $saveBtn.on('click', function() {
            var contentToSave = getPageContent();
            $saveBtn.text('...').prop('disabled', true);

            $.ajax({
                url: ibEditor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ib_save_content',
                    post_id: ibEditor.post_id,
                    nonce: ibEditor.nonce,
                    content: contentToSave
                },
                success: function(response) {
                    if (response.success) {
                        $saveBtn.text(response.data).css('background', '#20D50F');
                        setTimeout(function() {
                            $saveBtn.text('Guardar').prop('disabled', false).css('background', '');
                        }, 3000);
                    }
                }
            });
        });

        $downloadBtn.on('click', function() {
            var content = getPageContent();
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(content, null, 4));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "ib-content-page-" + ibEditor.post_id + ".json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });

        $resetBtn.on('click', function() {
            if (confirm("¿Borrar todos los cambios personalizados de esta página?")) {
                $.ajax({
                    url: ibEditor.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ib_reset_content',
                        post_id: ibEditor.post_id,
                        nonce: ibEditor.nonce
                    },
                    success: function(response) {
                        if (response.success) window.location.reload();
                    }
                });
            }
        });

        $undoBtn.on('click', function() {
            if (confirm("¿Deshacer el último guardado?")) {
                $undoBtn.prop('disabled', true).text('...');
                $.ajax({
                    url: ibEditor.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ib_undo_save',
                        post_id: ibEditor.post_id,
                        nonce: ibEditor.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.data || 'No hay backup para deshacer');
                            $undoBtn.prop('disabled', false).text('Deshacer');
                        }
                    }
                });
            }
        });

        function positionResetTip() {
            if (!$currentEditable) return;
            var offset = $currentEditable.offset();
            if (!offset) return;
            var scrollTop = $(window).scrollTop();
            var scrollLeft = $(window).scrollLeft();
            var tipH = $resetTip.outerHeight();
            $resetTip.css({
                left: (offset.left - scrollLeft) + 'px',
                top: (offset.top - scrollTop - tipH - 4) + 'px'
            });
        }

        function showResetTip($el) {
            $currentEditable = $el;
            positionResetTip();
            $resetTip.show();
        }

        function hideResetTip() {
            $resetTip.hide();
            $currentEditable = null;
        }

        $(window).on('scroll resize', function() {
            if ($resetTip.is(':visible')) {
                positionResetTip();
            }
        });

        $editables.on('click', function() {
            var $el = $(this);
            if (!$el.hasClass('ib-locked')) {
                showResetTip($el);
            }
        });

        $resetTip.on('click', function() {
            if (!$currentEditable) return;
            var key = $currentEditable.data('ib-editable');
            if (!key) return;

            if (!confirm('¿Resetear "' + key + '" a su valor por defecto?')) return;

            $.ajax({
                url: ibEditor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ib_reset_key',
                    post_id: ibEditor.post_id,
                    nonce: ibEditor.nonce,
                    key: key
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data || 'Error al resetear');
                    }
                }
            });
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#ib-key-reset-tip, [data-ib-editable]').length) {
                hideResetTip();
            }
        });

        $editables.on('mouseenter', function() {
            var $el = $(this);
            if (!$el.hasClass('ib-locked')) {
                $el.css('box-shadow', '0 0 0 2px rgba(32, 213, 15, 0.3)');
            }
        }).on('mouseleave', function() {
            $(this).css('box-shadow', 'none');
        });
    });

})(jQuery);
