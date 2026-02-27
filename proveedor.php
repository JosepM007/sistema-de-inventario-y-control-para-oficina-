<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';

$proveedores_validos = ['Amazon', 'Walmart', 'Siman', 'HP', 'Samsung', 'Apple', 'Lenovo'];

$prov = isset($_GET['prov']) ? $_GET['prov'] : '';

if (!in_array($prov, $proveedores_validos)) {
    header("Location: proveedores.php");
    exit;
}

$sql = "
SELECT p.id, p.nombre, p.descripcion, p.cantidad, p.precio, 
       p.proveedores, c.nombre AS categoria
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.proveedores = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $prov);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo $prov; ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="layout">

<div class="sidebar">
    <h2 class="logo">OfficeStock</h2>
    <a href="dashboard.php">🏠 Dashboard</a>
    <a href="categorias.php">📂 Categorías</a>
    <a href="proveedores.php">🏢 Proveedores</a>
    <a href="logout.php">🚪 Cerrar Sesión</a>
</div>

<div class="main-content">
    <h2>Proveedor: <?php echo $prov; ?></h2>

    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Categoría</th>
            <th>Cantidad</th>
            <th>Precio</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['descripcion']; ?></td>
            <td><?php echo $row['categoria']; ?></td>
            <td><?php echo $row['cantidad']; ?></td>
            <td>$<?php echo number_format($row['precio'],2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <br>
    <a href="proveedores.php">⬅ Volver</a>

</div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>