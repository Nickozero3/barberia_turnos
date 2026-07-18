<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_admin();

$pdo = db();
$editId = (int) ($_GET['edit'] ?? 0);
$editProduct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle' && $id > 0) {
        $stmt = $pdo->prepare('UPDATE products SET active = IF(active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Estado del producto actualizado.');
        redirect('/admin/productos.php');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $price = max(0, (int) ($_POST['price'] ?? 0));
    $cost = max(0, (int) ($_POST['cost'] ?? 0));
    $stock = (int) ($_POST['stock'] ?? 0);
    $minStock = max(0, (int) ($_POST['min_stock'] ?? 0));

    if ($name === '') {
        flash('danger', 'El nombre del producto es obligatorio.');
        redirect('/admin/productos.php' . ($id ? '?edit=' . $id : ''));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE products SET name = ?, category = ?, price = ?, cost = ?, stock = ?, min_stock = ? WHERE id = ?');
        $stmt->execute([$name, $category ?: null, $price, $cost, $stock, $minStock, $id]);
        flash('success', 'Producto actualizado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO products (name, category, price, cost, stock, min_stock) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $category ?: null, $price, $cost, $stock, $minStock]);
        flash('success', 'Producto agregado.');
    }
    redirect('/admin/productos.php');
}

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch() ?: null;
}

$products = $pdo->query('SELECT * FROM products ORDER BY active DESC, name')->fetchAll();
$pageTitle = 'Productos';
$activePage = 'products';
require __DIR__ . '/_header.php';
?>
<div class="page-head"><div><h1>Productos y stock</h1><p>Productos para vender al finalizar un turno.</p></div></div>

<div class="grid cols-2" style="align-items:start">
    <section class="card form-card">
        <h3><?= $editProduct ? 'Editar producto' : 'Nuevo producto' ?></h3>
        <p style="color:var(--muted)">El stock se descuenta cuando lo agregás a un turno.</p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">
            <div class="form-grid">
                <div class="field full"><label>Nombre</label><input class="input" name="name" value="<?= e($editProduct['name'] ?? '') ?>" required></div>
                <div class="field full"><label>Categoría</label><input class="input" name="category" value="<?= e($editProduct['category'] ?? '') ?>" placeholder="Cabello, barba…"></div>
                <div class="field"><label>Precio de venta</label><input class="input" type="number" name="price" min="0" step="1" value="<?= (int) ($editProduct['price'] ?? 0) ?>" required></div>
                <div class="field"><label>Costo</label><input class="input" type="number" name="cost" min="0" step="1" value="<?= (int) ($editProduct['cost'] ?? 0) ?>"></div>
                <div class="field"><label>Stock actual</label><input class="input" type="number" name="stock" step="1" value="<?= (int) ($editProduct['stock'] ?? 0) ?>"></div>
                <div class="field"><label>Stock mínimo</label><input class="input" type="number" name="min_stock" min="0" step="1" value="<?= (int) ($editProduct['min_stock'] ?? 0) ?>"></div>
            </div>
            <div class="input-row" style="margin-top:18px">
                <button class="btn btn-primary" type="submit"><?= $editProduct ? 'Guardar cambios' : 'Agregar producto' ?></button>
                <?php if ($editProduct): ?><a class="btn btn-light" href="/admin/productos.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php if (!$products): ?>
                    <tr><td colspan="5"><div class="empty-box">Todavía no hay productos cargados.</div></td></tr>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <?php $low = (int) $product['stock'] <= (int) $product['min_stock']; ?>
                    <tr>
                        <td><strong><?= e($product['name']) ?></strong><br><small style="color:var(--muted)"><?= e($product['category']) ?></small></td>
                        <td><?= money($product['price']) ?></td>
                        <td><span class="badge <?= $low ? 'warning' : 'success' ?>"><?= (int) $product['stock'] ?> unidades</span></td>
                        <td><span class="badge <?= $product['active'] ? 'success' : 'muted' ?>"><?= $product['active'] ? 'Activo' : 'Oculto' ?></span></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-light btn-sm" href="?edit=<?= (int) $product['id'] ?>">Editar</a>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="btn btn-sm <?= $product['active'] ? 'btn-danger' : 'btn-success' ?>" type="submit"><?= $product['active'] ? 'Ocultar' : 'Activar' ?></button></form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
