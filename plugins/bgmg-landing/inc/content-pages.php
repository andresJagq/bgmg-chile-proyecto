<?php
/**
 * Páginas de contenido branded: FAQ, Envíos, Devoluciones, Términos, Privacidad.
 *
 * - Define el contenido HTML semilla de cada página (datos tomados de
 *   beautygirlmg.cl: footer + FAQ).
 * - Crea las páginas en WP de forma IDEMPOTENTE: solo si NO existen (para no pisar
 *   ediciones del admin) y les asigna el template branded 'bgmg-page.php'.
 * - El seeding corre una vez en admin (gated por la opción 'bgmg_content_pages_seeded'),
 *   porque al actualizar el plugin por reemplazo de archivos NO se redispara el
 *   register_activation_hook.
 *
 * NOTA: La razón social y el RUT quedan como [PLACEHOLDER] en Términos y Privacidad
 * para que el dueño los complete desde wp-admin (Páginas → editar).
 */
defined( 'ABSPATH' ) || exit;

/**
 * Mapa de páginas de contenido: slug => [title, callback que devuelve el HTML].
 *
 * @return array
 */
function bgmg_content_pages_map() {
	return array(
		'preguntas-frecuentes'     => array( 'title' => 'Preguntas frecuentes',  'cb' => 'bgmg_content_faq' ),
		'politica-de-envios'       => array( 'title' => 'Política de envíos',     'cb' => 'bgmg_content_envios' ),
		'politica-de-devoluciones' => array( 'title' => 'Cambios y devoluciones', 'cb' => 'bgmg_content_devoluciones' ),
		'terminos-y-condiciones'   => array( 'title' => 'Términos y condiciones', 'cb' => 'bgmg_content_terminos' ),
		'politica-de-privacidad'   => array( 'title' => 'Política de privacidad', 'cb' => 'bgmg_content_privacidad' ),
	);
}

/**
 * Crea las páginas de contenido que falten y les asigna el template branded.
 * Idempotente: NO sobrescribe páginas existentes (preserva ediciones del admin);
 * a las que ya existan solo les asegura el template.
 */
function bgmg_seed_content_pages() {
	foreach ( bgmg_content_pages_map() as $slug => $page ) {
		$existing = get_page_by_path( $slug );

		if ( $existing ) {
			if ( get_page_template_slug( $existing->ID ) !== 'bgmg-page.php' ) {
				update_post_meta( $existing->ID, '_wp_page_template', 'bgmg-page.php' );
			}
			continue;
		}

		$id = wp_insert_post( array(
			'post_title'     => $page['title'],
			'post_name'      => $slug,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => call_user_func( $page['cb'] ),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		) );

		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'bgmg-page.php' );
		}
	}
}

/**
 * Dispara el seeding una sola vez tras subir/actualizar el plugin. Se usa una
 * opción versionada en vez del activation hook (que no corre al actualizar por zip).
 * Si en el futuro se agregan páginas, sube el valor a 'v2' (el seeder es
 * create-if-missing, así que re-correrlo es seguro).
 */
add_action( 'admin_init', function () {
	if ( get_option( 'bgmg_content_pages_seeded' ) === 'v1' ) {
		return;
	}
	bgmg_seed_content_pages();
	update_option( 'bgmg_content_pages_seeded', 'v1' );
} );

/* ════════════════════════════════════════════════════════════════════════════
 * CONTENIDO DE LAS PÁGINAS
 * Cada función devuelve HTML semántico (h2/h3/p/ul/details). Links relativos
 * (/slug/) para que funcionen igual en V1 y V2 sin hardcodear el dominio.
 * ════════════════════════════════════════════════════════════════════════════ */

/**
 * Preguntas frecuentes (acordeón <details>).
 */
function bgmg_content_faq() {
	return <<<'HTML'
<p class="bgmg-note">Lee con atención: aquí respondemos las dudas más comunes. Si te queda alguna, escríbenos por <a href="https://wa.me/56945362142" target="_blank" rel="noopener">WhatsApp</a> o a <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a>. 🌸</p>
<h2>Compras y pago</h2>
<details>
<summary>¿Cómo comprar?</summary>
<p>Agrega productos dando clic en el botón <strong>Agregar al carrito</strong>. Cuando quieras finalizar la compra, ve al <strong>carrito de compra</strong> y haz clic en <strong>Pagar</strong>. Completa los datos de compra y envío. Recibirás un email con la confirmación de tu compra.</p>
</details>
<details>
<summary>¿Cuáles son las formas de pago?</summary>
<p>El pago puede ser a través de <strong>Transbank Webpay</strong> y <strong>transferencia</strong>. Cuando seleccionas Transbank Webpay, serás redirigido al portal de pago donde podrás elegir el tipo de tarjeta: <strong>crédito, débito o prepago</strong>. Luego escoges tu banco e ingresas a tu cuenta bancaria para pagar.</p>
<p>Si el pago es por transferencia, al finalizar el pedido recibirás un correo con el detalle del pedido junto con la información para transferir.</p>
</details>
<details>
<summary>¿Qué medios de pago aceptan?</summary>
<p>Aceptamos <strong>cualquier medio de pago vigente en Chile</strong>: tarjetas de crédito, débito, prepago, transferencias bancarias y Webpay.</p>
</details>
<details>
<summary>¿Los precios incluyen IVA?</summary>
<p>No, los precios publicados <strong>no incluyen IVA</strong>.</p>
</details>
<details>
<summary>¿Hay monto mínimo de compra?</summary>
<p><strong>No hay monto mínimo de compra.</strong> Sin embargo, mientras más compres, puedes acceder a mejores precios. ¡Recuerda que por mayor todo es surtido!</p>
</details>
<h2>Envíos y entregas</h2>
<details>
<summary>¿Cuánto se demora en llegar mi pedido?</summary>
<p><strong>Santiago:</strong> las entregas son entre <strong>1 y 2 días hábiles</strong> luego de procesado tu pedido.</p>
<p><strong>Regiones:</strong> los envíos se realizan entre <strong>1 y 2 días hábiles</strong> luego de procesado tu pedido. El tiempo de entrega final depende de la agencia de envío. Al despachar recibirás el comprobante con la fecha estimada de entrega.</p>
</details>
<details>
<summary>¿Cuánto cuesta el envío?</summary>
<p><strong>Santiago:</strong> tenemos un monto fijo de <strong>$3.500</strong> con nuestra agencia de reparto interna. También puedes solicitar el envío por <strong>Starken o Blueexpress</strong> con envío por pagar.</p>
<p><strong>Regiones:</strong> enviamos a todo Chile a través de <strong>Starken o Blueexpress</strong>. El costo varía según el peso y la distancia del paquete. Este monto es <strong>por pagar</strong>: el cliente paga al recibir el producto. Con Starken contamos con <strong>20% de descuento</strong> sobre el total del envío.</p>
</details>
<details>
<summary>¿Se envía a domicilio?</summary>
<p><strong>Santiago:</strong> sí, se envía a domicilio con nuestra agencia de reparto interna, o también puedes solicitar envío por Starken o Blueexpress.</p>
<p><strong>Regiones:</strong> sí, puede ser enviado a domicilio o a sucursal a través de Starken o Blueexpress.</p>
</details>
<details>
<summary>¿Cómo hago seguimiento de mi pedido?</summary>
<p>Una vez realizado el envío, recibirás un <strong>código de seguimiento</strong> que te permitirá rastrear tu pedido hasta que llegue a destino. Recibirás el código entre <strong>24 y 48 horas (1 a 2 días hábiles)</strong> desde que se confirma el pago y se procesa tu pedido.</p>
</details>
<details>
<summary>¿Puedo retirar en tienda?</summary>
<p>Sí, puedes retirar o comprar presencialmente en nuestra tienda física.</p>
<p>📍 <strong>Dirección:</strong> Antonia López de Bello 461, Recoleta, Región Metropolitana.<br>📞 <strong>Teléfono:</strong> +56 9 4536 2142.<br>🕒 <strong>Horarios:</strong> lunes a viernes de 10:00 a 17:30 hrs; sábados de 10:00 a 15:30 hrs.</p>
<p>📩 Te avisaremos por correo o WhatsApp cuando tu pedido esté listo para retiro.</p>
</details>
<h2>Facturación</h2>
<details>
<summary>¿Hacen factura o boleta electrónica?</summary>
<p>Sí, hacemos tanto <strong>factura como boleta electrónica</strong>. Escríbenos por <strong>WhatsApp</strong> y tomaremos tu pedido con los datos correspondientes.</p>
</details>
<h2>Cambios y devoluciones</h2>
<details>
<summary>⚠️ Importante: reacciones alérgicas</summary>
<p>🌸 <strong>Cada piel es diferente y única.</strong> En nuestra tienda física contamos con <strong>testers disponibles</strong> para que puedas probar los productos antes de comprarlos.</p>
<p>✅ <strong>Método seguro de prueba:</strong> aplica una pequeña cantidad del producto en la <strong>parte interna de la muñeca o detrás de la oreja</strong> y espera al menos <strong>24 horas</strong> para observar cualquier reacción. Esto es especialmente importante si tienes piel sensible o historial de alergias.</p>
<p>❌ <strong>No se aceptan cambios ni devoluciones por reacciones alérgicas.</strong> Al realizar tu compra, aceptas haber leído esta advertencia y asumes la responsabilidad de probar los productos cuando sea posible.</p>
</details>
<details>
<summary>Políticas de cambios y devoluciones</summary>
<p>✅ <strong>Garantía legal:</strong> puedes solicitar cambio o devolución si el producto presenta una <strong>falla de fábrica, hasta 3 días hábiles</strong> después de la compra. El producto debe restituirse en perfecto estado: <strong>sin uso, sellado, con etiquetas, accesorios y embalaje original</strong>, sin haber sido testeado. Esta garantía está sujeta a evaluación y aprobación.</p>
<p>📄 <strong>Requisito obligatorio:</strong> es obligatorio presentar tu <strong>boleta o ticket de cambio</strong> para cualquier solicitud, sin excepciones.</p>
<p>❌ <strong>No aceptamos cambios ni devoluciones por:</strong></p>
<ul>
<li>Derecho a retracto.</li>
<li>Satisfacción o gustos personales.</li>
<li>Productos abiertos o usados.</li>
<li>Reacciones alérgicas (ver sección específica).</li>
</ul>
<p>💵 <strong>Tiempos de reembolso:</strong></p>
<ul>
<li>Efectivo: 3 días hábiles vía transferencia bancaria.</li>
<li>Tarjeta de débito: 7 días hábiles.</li>
<li>Tarjeta de crédito: 14 días hábiles.</li>
</ul>
<p>El monto se devuelve a la misma cuenta o tarjeta con la que realizaste la compra. Para iniciar el proceso, deberás devolver los productos en nuestra tienda física o enviarlos por encomienda si eres de región. Revisa el detalle en nuestra <a href="/politica-de-devoluciones/">Política de cambios y devoluciones</a>.</p>
<p>📌 Nos reservamos el derecho de aceptar o rechazar cambios, devoluciones o reembolsos tras analizar si se cumplen todos los requisitos establecidos.</p>
</details>
<h2>Modificar mi pedido</h2>
<details>
<summary>¿Qué puedo hacer si me equivoqué con mi pedido?</summary>
<p>Si hay algún error con tu pedido, comunícate de inmediato al correo <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a> o por WhatsApp. Indica tu número de pedido y los productos a rectificar. Esto será válido <strong>antes del envío</strong>. Si el pedido ya fue enviado, no se podrán hacer cambios.</p>
</details>
<details>
<summary>¿Qué puedo hacer si olvidé añadir un producto?</summary>
<p>Si olvidaste añadir productos, escríbenos por <strong>WhatsApp</strong> indicando tu número de pedido y lo que deseas agregar. Será válido solo si lo haces de inmediato. Si el pedido ya fue enviado, no se pueden realizar modificaciones.</p>
</details>
HTML;
}

/**
 * Política de envíos.
 */
function bgmg_content_envios() {
	return <<<'HTML'
<p class="bgmg-note">En <strong>BeautyGirl MG</strong> despachamos a todo Chile. Aquí encuentras la cobertura, costos, plazos y seguimiento de tu pedido.</p>
<h2>Cobertura</h2>
<p>Realizamos envíos a <strong>todo Chile</strong>. En Santiago contamos con reparto propio; a regiones despachamos mediante <strong>Starken</strong> o <strong>Blueexpress</strong>, a domicilio o a sucursal.</p>
<h2>Costos de envío</h2>
<h3>Santiago</h3>
<p>Monto fijo de <strong>$3.500</strong> con nuestra agencia de reparto interna. También puedes solicitar envío por <strong>Starken o Blueexpress</strong> en modalidad <strong>por pagar</strong>.</p>
<h3>Regiones</h3>
<p>Enviamos a través de <strong>Starken o Blueexpress</strong>. El costo varía según el <strong>peso y la distancia</strong> del paquete y se cancela en modalidad <strong>por pagar</strong> (el cliente paga al recibir el producto). Con Starken aplicamos un <strong>20% de descuento</strong> sobre el total del envío.</p>
<h2>Plazos de entrega</h2>
<p>Los pedidos se entregan entre <strong>1 y 2 días hábiles</strong> luego de procesado el pago, tanto en Santiago como en regiones. El tiempo final en regiones depende de la agencia de transporte.</p>
<h2>Seguimiento</h2>
<p>Una vez despachado tu pedido recibirás un <strong>código de seguimiento</strong> para rastrearlo hasta su destino. El código llega entre <strong>24 y 48 horas (1 a 2 días hábiles)</strong> desde que se confirma el pago.</p>
<h2>Retiro en tienda</h2>
<p>Puedes retirar tu pedido (o comprar presencialmente) en nuestra tienda física:</p>
<p>📍 Antonia López de Bello 461, Recoleta, Región Metropolitana.<br>🕒 Lunes a viernes de 10:00 a 17:30 hrs; sábados de 10:00 a 15:30 hrs.</p>
<p>Te avisaremos por correo o WhatsApp cuando tu pedido esté listo para retiro.</p>
<h2>¿Dudas con tu envío?</h2>
<p>Escríbenos por <a href="https://wa.me/56945362142" target="_blank" rel="noopener">WhatsApp (+56 9 4536 2142)</a> o a <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a> indicando tu número de pedido.</p>
HTML;
}

/**
 * Cambios y devoluciones.
 */
function bgmg_content_devoluciones() {
	return <<<'HTML'
<p class="bgmg-note">Queremos que tu compra sea una buena experiencia. Aquí explicamos cómo gestionar <strong>cambios y devoluciones</strong> en BeautyGirl MG.</p>
<h2>Garantía legal</h2>
<p>Puedes solicitar cambio o devolución si el producto presenta una <strong>falla de fábrica</strong>, hasta <strong>3 días hábiles</strong> después de la compra. El producto debe restituirse en perfecto estado: <strong>sin uso, sellado, con etiquetas, accesorios y embalaje original</strong>, sin haber sido testeado. Toda solicitud queda sujeta a evaluación y aprobación.</p>
<h2>Requisito obligatorio</h2>
<p>Es obligatorio presentar tu <strong>boleta o ticket de cambio</strong> para cualquier solicitud, sin excepciones.</p>
<h2>Situaciones que no aceptamos</h2>
<ul>
<li>Derecho a retracto.</li>
<li>Satisfacción o gustos personales.</li>
<li>Productos abiertos o usados.</li>
<li>Reacciones alérgicas (ver más abajo).</li>
</ul>
<h2>Reacciones alérgicas</h2>
<p>Cada piel es diferente. En nuestra tienda física contamos con <strong>testers</strong> para que pruebes los productos antes de comprar. Te recomendamos aplicar una pequeña cantidad en la parte interna de la muñeca o detrás de la oreja y esperar al menos <strong>24 horas</strong>. <strong>No se aceptan cambios ni devoluciones por reacciones alérgicas.</strong></p>
<h2>Tiempos de reembolso</h2>
<ul>
<li><strong>Efectivo:</strong> 3 días hábiles vía transferencia bancaria.</li>
<li><strong>Tarjeta de débito:</strong> 7 días hábiles.</li>
<li><strong>Tarjeta de crédito:</strong> 14 días hábiles.</li>
</ul>
<p>El monto se devuelve a la misma cuenta o tarjeta con la que se realizó la compra.</p>
<h2>¿Cómo inicio el proceso?</h2>
<p>Devuelve los productos en nuestra tienda física (Antonia López de Bello 461, Recoleta) o envíalos por encomienda si eres de región. También puedes escribirnos por <a href="https://wa.me/56945362142" target="_blank" rel="noopener">WhatsApp</a> o a <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a>.</p>
<p>📌 Nos reservamos el derecho de aceptar o rechazar cambios, devoluciones o reembolsos tras verificar el cumplimiento de todos los requisitos.</p>
HTML;
}

/**
 * Términos y condiciones.
 */
function bgmg_content_terminos() {
	return <<<'HTML'
<p class="bgmg-note">Estos Términos y Condiciones regulan el uso del sitio y la compra de productos en BeautyGirl MG. Al realizar un pedido, declaras haber leído y aceptado estas condiciones.</p>
<h2>1. Identificación del proveedor</h2>
<p>Sitio operado por <strong>[RAZÓN SOCIAL]</strong>, RUT <strong>[RUT]</strong>, con domicilio comercial en Antonia López de Bello 461, Recoleta, Región Metropolitana, Chile. Contacto: <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a> · <a href="https://wa.me/56945362142" target="_blank" rel="noopener">+56 9 4536 2142</a>.</p>
<h2>2. Aceptación</h2>
<p>El uso del sitio y la realización de compras implican la aceptación plena de estos Términos y Condiciones, así como de nuestra <a href="/politica-de-privacidad/">Política de privacidad</a>, <a href="/politica-de-envios/">Política de envíos</a> y <a href="/politica-de-devoluciones/">Política de cambios y devoluciones</a>.</p>
<h2>3. Productos y precios</h2>
<p>Procuramos que la información, imágenes y precios de los productos sean correctos. Los colores pueden variar levemente según la pantalla. Los precios están expresados en pesos chilenos (CLP) y <strong>no incluyen IVA</strong>, salvo que se indique lo contrario. Nos reservamos el derecho de modificar precios y disponibilidad sin previo aviso; el precio aplicable es el vigente al momento de confirmar el pedido.</p>
<p>Las compras por mayor se despachan en modalidad de <strong>surtido</strong>. No existe monto mínimo de compra.</p>
<h2>4. Proceso de compra</h2>
<p>Para comprar, agrega los productos al carrito y completa el proceso de pago con tus datos de contacto y envío. Recibirás un correo con la confirmación. La compra queda sujeta a la confirmación del pago y a la disponibilidad de stock.</p>
<h2>5. Medios de pago</h2>
<p>Aceptamos los medios de pago vigentes en Chile: <strong>Transbank Webpay</strong> (crédito, débito y prepago) y <strong>transferencia bancaria</strong>. Los pagos con tarjeta se procesan en el entorno seguro de Transbank; BeautyGirl MG no almacena los datos de tu tarjeta.</p>
<h2>6. Boleta y factura</h2>
<p>Emitimos boleta y factura electrónica. Para factura, escríbenos por WhatsApp con los datos correspondientes antes de despachar tu pedido.</p>
<h2>7. Envíos</h2>
<p>Los plazos, costos y cobertura de despacho se detallan en nuestra <a href="/politica-de-envios/">Política de envíos</a>. Los plazos son estimados y pueden variar por causas ajenas a BeautyGirl MG (agencias de transporte, fuerza mayor, etc.).</p>
<h2>8. Cambios, devoluciones y garantía legal</h2>
<p>Las condiciones de cambios y devoluciones se detallan en nuestra <a href="/politica-de-devoluciones/">Política de cambios y devoluciones</a>. Se respeta la garantía legal establecida en la <strong>Ley N° 19.496 sobre Protección de los Derechos de los Consumidores</strong> para productos con falla de fábrica.</p>
<h2>9. Responsabilidad sobre el uso de los productos</h2>
<p>Cada piel es distinta. Recomendamos realizar una prueba de tolerancia antes de usar cualquier producto cosmético. BeautyGirl MG no se hace responsable por reacciones alérgicas derivadas del uso de los productos. Usa los productos siguiendo sus instrucciones.</p>
<h2>10. Propiedad intelectual</h2>
<p>Los contenidos del sitio (textos, imágenes, logotipos y marca) son propiedad de BeautyGirl MG o de sus respectivos titulares y están protegidos por la legislación vigente. No está permitida su reproducción sin autorización.</p>
<h2>11. Protección de datos</h2>
<p>El tratamiento de tus datos personales se rige por nuestra <a href="/politica-de-privacidad/">Política de privacidad</a>, conforme a la Ley N° 19.628 sobre Protección de la Vida Privada.</p>
<h2>12. Legislación aplicable</h2>
<p>Estos Términos y Condiciones se rigen por las leyes de la República de Chile. Ante cualquier inconveniente, puedes contactarnos directamente; asimismo, el Servicio Nacional del Consumidor (SERNAC) está disponible como instancia de mediación.</p>
<p class="bgmg-page-updated">Última actualización: junio de 2026.</p>
HTML;
}

/**
 * Política de privacidad.
 */
function bgmg_content_privacidad() {
	return <<<'HTML'
<p class="bgmg-note">En BeautyGirl MG respetamos tu privacidad. Esta política explica qué datos personales recopilamos, con qué fin y cuáles son tus derechos, conforme a la <strong>Ley N° 19.628 sobre Protección de la Vida Privada</strong>.</p>
<h2>1. Responsable del tratamiento</h2>
<p><strong>[RAZÓN SOCIAL]</strong>, RUT <strong>[RUT]</strong>, con domicilio en Antonia López de Bello 461, Recoleta, Región Metropolitana. Contacto: <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a>.</p>
<h2>2. Qué datos recopilamos</h2>
<ul>
<li><strong>Datos de identificación y contacto:</strong> nombre, correo electrónico y teléfono.</li>
<li><strong>Datos de envío:</strong> dirección de despacho.</li>
<li><strong>Datos de la compra:</strong> productos adquiridos, montos e historial de pedidos.</li>
<li><strong>Datos de navegación:</strong> información técnica y de uso del sitio mediante cookies (ver sección 6).</li>
</ul>
<p>Los pagos con tarjeta se procesan directamente por <strong>Transbank</strong>; <strong>no almacenamos los datos de tu tarjeta</strong>.</p>
<h2>3. Para qué usamos tus datos</h2>
<ul>
<li>Procesar y despachar tus pedidos.</li>
<li>Emitir boletas y facturas.</li>
<li>Contactarte por temas relacionados con tu compra.</li>
<li>Brindar soporte y atención al cliente.</li>
<li>Enviarte información o promociones, solo si lo autorizas.</li>
</ul>
<h2>4. Consentimiento</h2>
<p>Al entregarnos tus datos y realizar una compra, autorizas su tratamiento para las finalidades descritas. Puedes revocar esta autorización en cualquier momento escribiéndonos.</p>
<h2>5. Con quién compartimos tus datos</h2>
<p>Solo compartimos los datos necesarios con terceros que nos permiten prestar el servicio: <strong>empresas de transporte</strong> (Starken, Blueexpress) para el despacho y <strong>la pasarela de pago</strong> (Transbank) para procesar el cobro. No vendemos ni cedemos tus datos a terceros con fines comerciales ajenos.</p>
<h2>6. Cookies</h2>
<p>Utilizamos cookies para el funcionamiento del carrito, recordar tus preferencias y mejorar tu experiencia. Puedes administrar o bloquear las cookies desde la configuración de tu navegador; ten en cuenta que algunas funciones podrían dejar de operar correctamente.</p>
<h2>7. Tus derechos</h2>
<p>Puedes solicitar en cualquier momento el <strong>acceso, rectificación, cancelación u oposición</strong> al tratamiento de tus datos personales escribiéndonos a <a href="mailto:contacto@beautygirlmg.cl">contacto@beautygirlmg.cl</a>.</p>
<h2>8. Seguridad</h2>
<p>Adoptamos medidas razonables para proteger tus datos frente a accesos no autorizados, pérdida o alteración. El sitio opera bajo conexión segura (HTTPS).</p>
<h2>9. Cambios a esta política</h2>
<p>Podemos actualizar esta política para reflejar cambios legales u operativos. La versión vigente siempre estará publicada en esta página.</p>
<p class="bgmg-page-updated">Última actualización: junio de 2026.</p>
HTML;
}
