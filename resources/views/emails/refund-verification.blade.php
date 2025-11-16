<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Devolución</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
            color: #333333;
            line-height: 1.6;
        }
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box strong {
            color: #1e40af;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .button-approve {
            background-color: #10b981;
            color: #ffffff;
        }
        .button-approve:hover {
            background-color: #059669;
        }
        .button-reject {
            background-color: #ef4444;
            color: #ffffff;
        }
        .button-reject:hover {
            background-color: #dc2626;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid: #f59e0b;
            padding: 15px;
            margin: 20px 0;
            color: #92400e;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Solicitud de Devolución</h1>
        </div>

        <div class="content">
            <p>Estimado/a <strong>{{ $emailData['passenger_name'] }}</strong>,</p>

            <p>El chofer <strong>{{ $emailData['driver_name'] }}</strong> ha solicitado realizar una devolución de un cobro realizado durante su viaje.</p>

            <div class="info-box">
                <p><strong>Detalles de la Devolución:</strong></p>
                <p><strong>Monto:</strong> {{ $emailData['amount'] }} Bs</p>
                <p><strong>Motivo:</strong> {{ $emailData['reason'] }}</p>
            </div>

            <p>Para procesar esta devolución, necesitamos su confirmación. Por favor, seleccione una de las siguientes opciones:</p>

            <div class="button-container">
                <a href="{{ $emailData['verification_url'] }}?action=approve" class="button button-approve">
                    ✓ Aprobar Devolución
                </a>
                <a href="{{ $emailData['verification_url'] }}?action=reject" class="button button-reject">
                    ✗ Rechazar Devolución
                </a>
            </div>

            <div class="warning">
                <p><strong>⚠️ Importante:</strong></p>
                <ul>
                    <li>Este enlace expirará el <strong>{{ $emailData['expires_at'] }}</strong></li>
                    <li>Si aprueba la devolución, el monto será acreditado automáticamente a su tarjeta</li>
                    <li>Si rechaza la solicitud, el cobro permanecerá como está</li>
                </ul>
            </div>

            <p>Si usted no realizó ninguna transacción recientemente o considera que este correo es un error, por favor ignore este mensaje o comuníquese con soporte.</p>

            <p>Gracias por usar nuestro sistema de transporte.</p>

            <p>Atentamente,<br>
            <strong>Sistema de Cobro de Transporte</strong></p>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responda a este mensaje.</p>
            <p>&copy; {{ date('Y') }} Sistema de Transporte. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
