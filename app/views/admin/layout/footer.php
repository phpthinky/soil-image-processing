    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview for uploads
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        
        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = '<?php echo BASE_URL; ?>admin/dashboard?delete=' + id;
            }
        }
    </script>
</body>
</html>