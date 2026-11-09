<?php

declare(strict_types=1);

/**
 * @var array $contacto
 */
$contactEmail = (string) ($contacto['email'] ?? '');
?>
<section class="section">
  <div class="container">
    <h1>Política de privacidad</h1>

    <p>El Software Factory Lab (SFL) de la Universidad de La Serena trata los datos personales que recibe a través de este sitio conforme a la Ley N° 19.628 sobre Protección de la Vida Privada y a la Ley N° 21.719.</p>

    <h2>Qué datos recopilamos</h2>
    <p>Solo los que ingresas en el formulario de contacto: nombre, correo, teléfono (opcional), tipo de interacción y mensaje. Para el panel interno se registran además las cuentas del equipo y su actividad, con fines de seguridad y auditoría.</p>

    <h2>Para qué los usamos</h2>
    <p>Los datos del formulario de contacto se usan únicamente para responder tu solicitud. No se ceden a terceros ni se usan con fines comerciales. Solo se registran si das tu consentimiento explícito al enviar el formulario.</p>

    <h2>Tus derechos</h2>
    <p>Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, portabilidad y bloqueo (ARCO+)<?php if ($contactEmail !== '') : ?> escribiendo a <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php else : ?> a través del <a href="/contacto">formulario de contacto</a><?php endif; ?>.</p>
  </div>
</section>
