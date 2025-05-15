<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .product-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .iframe-code {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Product Dashboard</h1>
            <a href="login.php?logout=true" class="btn btn-danger">Logout</a>
        </div>
        <div class="mb-3">
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addProductModal">Add Product</button>
            <button class="btn btn-success me-2" onclick="exportData()">Export JSON</button>
            <label for="importFile" class="btn btn-warning">Import JSON</label>
            <input type="file" id="importFile" accept=".json" style="display: none;" onchange="importData(event)">
        </div>

        <!-- Tabel Produk -->
        <table class="table table-bordered product-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Affiliate Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody"></tbody>
        </table>

        <!-- Iframe Code -->
        <div class="mt-4">
            <h3>Iframe Code for Ads</h3>
            <div class="iframe-code">
                <code id="iframeCode"><iframe src="ads.html" width="100%" height="300px" frameborder="0"></iframe></code>
                <button class="btn btn-sm btn-secondary copy-btn" onclick="copyIframeCode()">Copy</button>
            </div>
        </div>
    </div>

    <!-- Modal untuk Tambah/Edit Produk -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productIndex" value="-1">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="productName" required>
                        </div>
                        <div class="mb-3">
                            <label for="productPrice" class="form-label">Price</label>
                            <input type="text" class="form-control" id="productPrice" required>
                        </div>
                        <div class="mb-3">
                            <label for="productImage" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="productImage" required>
                        </div>
                        <div class="mb-3">
                            <label for="productAffiliate" class="form-label">Affiliate Link</label>
                            <input type="url" class="form-control" id="productAffiliate" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let products = [];

        // Load products from JSON
        fetch('products.json')
            .then(response => response.json())
            .then(data => {
                products = data;
                displayProducts();
            })
            .catch(error => console.error('Error loading products:', error));

        // Display products in table
        function displayProducts() {
            const tableBody = document.getElementById('productTableBody');
            tableBody.innerHTML = '';
            products.forEach((product, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><img src="${product.imageUrl}" alt="${product.name}"></td>
                    <td>${product.name}</td>
                    <td>${product.price}</td>
                    <td><a href="${product.affiliateLink}" target="_blank">Link</a></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editProduct(${index})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${index})">Delete</button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Save product form
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const index = parseInt(document.getElementById('productIndex').value);
            const product = {
                name: document.getElementById('productName').value,
                price: document.getElementById('productPrice').value,
                imageUrl: document.getElementById('productImage').value,
                affiliateLink: document.getElementById('productAffiliate').value
            };

            if (index === -1) {
                products.push(product);
            } else {
                products[index] = product;
            }

            // Save to JSON via PHP
            fetch('save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(products)
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      displayProducts();
                      bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
                      document.getElementById('productForm').reset();
                      document.getElementById('productIndex').value = -1;
                  } else {
                      alert('Failed to save data. Check server logs.');
                  }
              })
              .catch(error => console.error('Error saving products:', error));
        });

        // Edit product
        function editProduct(index) {
            const product = products[index];
            document.getElementById('productName').value = product.name;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productImage').value = product.imageUrl;
            document.getElementById('productAffiliate').value = product.affiliateLink;
            document.getElementById('productIndex').value = index;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addProductModal')).show();
        }

        // Delete product
        function deleteProduct(index) {
            if (confirm('Are you sure you want to delete this product?')) {
                products.splice(index, 1);
                fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(products)
                }).then(() => displayProducts());
            }
        }

        // Copy iframe code
        function copyIframeCode() {
            const iframeCode = document.getElementById('iframeCode').innerText;
            navigator.clipboard.writeText(iframeCode).then(() => {
                alert('Iframe code copied to clipboard!');
            });
        }

        // Export JSON
        function exportData() {
            const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(products, null, 2));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute('href', dataStr);
            downloadAnchorNode.setAttribute('download', 'products_backup_' + new Date().toISOString().slice(0, 10) + '.json');
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            document.body.removeChild(downloadAnchorNode);
        }

        // Import JSON
        function importData(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const importedProducts = JSON.parse(e.target.result);
                        products = importedProducts;
                        fetch('save.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(products)
                        }).then(() => {
                            displayProducts();
                            alert('Data imported successfully!');
                        });
                    } catch (error) {
                        alert('Invalid JSON file!');
                        console.error('Error parsing JSON:', error);
                    }
                };
                reader.readAsText(file);
            }
        }
    </script>
</body>
</html>