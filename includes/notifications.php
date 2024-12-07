<?php
function showNotification($message, $type = 'success') {
    echo "
    <script>
        Swal.fire({
            title: '" . ($type === 'success' ? 'Succès' : 'Erreur') . "',
            text: '" . addslashes($message) . "',
            icon: '" . $type . "',
            confirmButtonColor: '#FFCC00',
            confirmButtonText: 'OK'
        });
    </script>";
}
?> 