<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container container-large dashboard-container">
        
        <div class="header">
            <div>
                <h2 style="margin: 0;">Gestión de Usuarios</h2>
                <p class="user-info">👤 <?php echo $_SESSION['usuario']; ?> | Administrador</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary btn-small">← Volver</a>
                <a href="logout.php" class="btn btn-danger btn-small">Cerrar Sesión</a>
            </div>
        </div>
        
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Usuarios Registrados</h3>
                <a href="crear_usuario_admin.php" class="btn btn-success btn-small">➕ Crear Usuario</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>ID</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo $user['usuario']; ?></strong></td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                <?php if ($user['rol'] == 'admin'): ?>
                                    <span style="color: #d32f2f; font-weight: bold;">👑 Admin</span>
                                <?php else: ?>
                                    <span style="color: #1976d2;">👤 Usuario</span>
                                <?php endif; ?>
                            </td>
                            <td>—</td>
                            <td class="actions">
                                <?php if ($user['usuario'] != $_SESSION['usuario']): ?>
                                    <?php if ($user['rol'] == 'usuario'): ?>
                                        <a href="cambiar_rol.php?id=<?php echo $user['id']; ?>&rol=admin" 
                                           class="btn btn-success btn-small">👑 Hacer Admin</a>
                                    <?php else: ?>
                                        <a href="cambiar_rol.php?id=<?php echo $user['id']; ?>&rol=usuario" 
                                           class="btn btn-secondary btn-small">👤 Quitar Admin</a>
                                    <?php endif; ?>
                                    <a href="eliminar_usuario.php?id=<?php echo $user['id']; ?>" 
                                       onclick="return confirm('¿Eliminar este usuario?')" 
                                       class="btn btn-danger btn-small">🗑️ Eliminar</a>
                                <?php else: ?>
                                    <span style="color: #666; font-size: 12px;">Tu cuenta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>
<?php $conn->close(); ?>
