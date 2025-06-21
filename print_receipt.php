<?php
// print_receipt.php - Generar recibo imprimible
require_once 'config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Obtener ID de la cita
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id || !is_numeric($appointment_id)) {
    header('Location: dashboard.php');
    exit;
}

// Obtener datos de la cita (solo del usuario logueado)
$stmt = $conn->prepare("
    SELECT a.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
    DATE_FORMAT(a.appointment_date, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(a.appointment_time, '%H:%i') as formatted_time,
    DATE_FORMAT(a.created_at, '%d/%m/%Y %H:%i:%s') as formatted_created
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: dashboard.php');
    exit;
}

// Generar código QR simple (usando texto)
$qr_data = "Cita #{$appointment['id']} - {$appointment['formatted_date']} {$appointment['formatted_time']}";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Cita #<?php echo str_pad($appointment['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: white;
            color: #333;
            line-height: 1.6;
        }

        .receipt-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 40px;
            border: 2px solid #ddd;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 10px;
        }

        .clinic-info {
            color: #666;
            font-size: 14px;
        }

        .receipt-title {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 1.4em;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .appointment-number {
            text-align: center;
            font-size: 1.8em;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 30px;
            letter-spacing: 2px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .detail-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }

        .detail-row strong {
            color: #333;
        }

        .status-section {
            text-align: center;
            background: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
        }

        .status-section .status {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
        }

        .important-notes {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .important-notes h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .important-notes ul {
            color: #856404;
            margin-left: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
            color: #666;
            font-size: 12px;
        }

        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .no-print {
            text-align: center;
            margin: 20px 0;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            font-size: 14px;
        }

        .btn:hover {
            background: #5a67d8;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .receipt-container {
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 20px;
            }
            
            .no-print {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .receipt-container {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        
        <!-- Header -->
        <div class="header">
            <div class="logo">🐾 VetCitas</div>
            <div class="clinic-info">
                <strong>Clínica Veterinaria</strong><br>
                Sistema de Reservación de Citas<br>
                Tel: (555) 123-4567 | Email: info@vetcitas.com
            </div>
        </div>

        <!-- Título -->
        <div class="receipt-title">
            COMPROBANTE DE CITA MÉDICA
        </div>

        <!-- Número de cita -->
        <div class="appointment-number">
            #<?php echo str_pad($appointment['id'], 4, '0', STR_PAD_LEFT); ?>
        </div>

        <!-- Detalles -->
        <div class="details-grid">
            <div class="detail-section">
                <h3>📋 Información del Cliente</h3>
                <div class="detail-row">
                    <span>Nombre:</span>
                    <strong><?php echo htmlspecialchars($appointment['user_name']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Email:</span>
                    <strong><?php echo htmlspecialchars($appointment['user_email']); ?></strong>
                </div>
                <?php if ($appointment['user_phone']): ?>
                <div class="detail-row">
                    <span>Teléfono:</span>
                    <strong><?php echo htmlspecialchars($appointment['user_phone']); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <div class="detail-section">
                <h3>🐕 Información de la Cita</h3>
                <div class="detail-row">
                    <span>Mascota:</span>
                    <strong><?php echo htmlspecialchars($appointment['pet_name']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Servicio:</span>
                    <strong><?php echo htmlspecialchars($appointment['service']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Fecha:</span>
                    <strong><?php echo $appointment['formatted_date']; ?></strong>
                </div>
                <div class="detail-row">
                    <span>Hora:</span>
                    <strong><?php echo $appointment['formatted_time']; ?></strong>
                </div>
            </div>
        </div>

        <!-- Estado -->
        <div class="status-section">
            <div class="status">✅ CITA <?php echo strtoupper(htmlspecialchars($appointment['status'])); ?></div>
            <p>Reservado el: <?php echo $appointment['formatted_created']; ?></p>
        </div>

        <!-- Código de referencia -->
        <div class="qr-section">
            <strong>Código de Referencia:</strong><br>
            <code style="font-size: 14px; background: #e9ecef; padding: 5px 10px; border-radius: 4px;">
                <?php echo strtoupper(substr(md5($qr_data), 0, 8)); ?>
            </code>
        </div>

        <!-- Notas importantes -->
        <div class="important-notes">
            <h3>📌 Instrucciones Importantes</h3>
            <ul>
                <li>Llegue <strong>10 minutos antes</strong> de su cita</li>
                <li>Traiga la <strong>cartilla de vacunación</strong> de su mascota</li>
                <li>Si necesita cancelar, hágalo con <strong>24 horas de anticipación</strong></li>
                <li>En caso de emergencia, llame al: <strong>(555) 123-4567</strong></li>
                <li>Conserve este comprobante como referencia</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este comprobante es válido como confirmación de su cita médica.</p>
            <p>Generado automáticamente el <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>www.vetcitas.com | Gracias por confiar en nosotros 🐾</p>
        </div>

        <!-- Botones (no se imprimen) -->
        <div class="no-print">
            <button onclick="window.print()" class="btn">🖨️ Imprimir</button>
            <a href="dashboard.php" class="btn">← Volver al Panel</a>
        </div>
    </div>

    <script>
        // Auto-abrir diálogo de impresión si se especifica en la URL
        if (window.location.search.includes('print=true')) {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 1000);
            };
        }
    </script>
</body>
</html>