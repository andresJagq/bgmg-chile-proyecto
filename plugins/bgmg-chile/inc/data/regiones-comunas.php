<?php
/**
 * Dataset oficial de regiones y comunas de Chile.
 *
 * Fuente: división político-administrativa vigente (Subdere / BCN), 16 regiones
 * y 346 comunas. Códigos de región según ISO 3166-2:CL (sin prefijo "CL-",
 * porque WooCommerce los almacena así en wc_states['CL']).
 *
 * Las comunas usan un "slug" estable (snake_case sin acentos) como clave
 * primaria. Eso permite:
 *   - Persistir en BD sin sufrir cambios cosméticos en el nombre.
 *   - Comparar dirección de envío con la lista de tarifas RM sin ambigüedad.
 *
 * Para ordenamiento alfabético en frontend usamos el nombre con tildes.
 *
 * IMPORTANTE: si cambia la división administrativa (creación/fusión de
 * comunas), basta con tocar este archivo. Nada más en el plugin depende
 * de literales de comuna.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Las 16 regiones de Chile (código → nombre oficial).
 * Ordenadas geográficamente de norte a sur.
 */
function bgmg_chile_get_regiones() {
	return array(
		'AP' => 'Arica y Parinacota',
		'TA' => 'Tarapacá',
		'AN' => 'Antofagasta',
		'AT' => 'Atacama',
		'CO' => 'Coquimbo',
		'VS' => 'Valparaíso',
		'RM' => 'Metropolitana de Santiago',
		'LI' => "Libertador General Bernardo O'Higgins",
		'ML' => 'Maule',
		'NB' => 'Ñuble',
		'BI' => 'Biobío',
		'AR' => 'La Araucanía',
		'LR' => 'Los Ríos',
		'LL' => 'Los Lagos',
		'AI' => 'Aysén del General Carlos Ibáñez del Campo',
		'MA' => 'Magallanes y de la Antártica Chilena',
	);
}

/**
 * Helper interno para construir un array de comunas a partir de pares slug/nombre.
 */
function _bgmg_chile_comunas( array $pares ) {
	$out = array();
	foreach ( $pares as $slug => $nombre ) {
		$out[] = array(
			'slug'   => $slug,
			'nombre' => $nombre,
		);
	}
	return $out;
}

/**
 * Mapa completo: código región → lista de comunas.
 *
 * @return array<string, array<int, array{slug:string,nombre:string}>>
 */
function bgmg_chile_get_comunas_por_region() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = array(
		'AP' => _bgmg_chile_comunas( array(
			'arica'          => 'Arica',
			'camarones'      => 'Camarones',
			'general_lagos'  => 'General Lagos',
			'putre'          => 'Putre',
		) ),
		'TA' => _bgmg_chile_comunas( array(
			'alto_hospicio' => 'Alto Hospicio',
			'camina'        => 'Camiña',
			'colchane'      => 'Colchane',
			'huara'         => 'Huara',
			'iquique'       => 'Iquique',
			'pica'          => 'Pica',
			'pozo_almonte'  => 'Pozo Almonte',
		) ),
		'AN' => _bgmg_chile_comunas( array(
			'antofagasta'           => 'Antofagasta',
			'calama'                => 'Calama',
			'maria_elena'           => 'María Elena',
			'mejillones'            => 'Mejillones',
			'ollague'               => 'Ollagüe',
			'san_pedro_de_atacama'  => 'San Pedro de Atacama',
			'sierra_gorda'          => 'Sierra Gorda',
			'taltal'                => 'Taltal',
			'tocopilla'             => 'Tocopilla',
		) ),
		'AT' => _bgmg_chile_comunas( array(
			'alto_del_carmen'  => 'Alto del Carmen',
			'caldera'          => 'Caldera',
			'chanaral'         => 'Chañaral',
			'copiapo'          => 'Copiapó',
			'diego_de_almagro' => 'Diego de Almagro',
			'freirina'         => 'Freirina',
			'huasco'           => 'Huasco',
			'tierra_amarilla'  => 'Tierra Amarilla',
			'vallenar'         => 'Vallenar',
		) ),
		'CO' => _bgmg_chile_comunas( array(
			'andacollo'    => 'Andacollo',
			'canela'       => 'Canela',
			'combarbala'   => 'Combarbalá',
			'coquimbo'     => 'Coquimbo',
			'illapel'      => 'Illapel',
			'la_higuera'   => 'La Higuera',
			'la_serena'    => 'La Serena',
			'los_vilos'    => 'Los Vilos',
			'monte_patria' => 'Monte Patria',
			'ovalle'       => 'Ovalle',
			'paihuano'     => 'Paihuano',
			'punitaqui'    => 'Punitaqui',
			'rio_hurtado'  => 'Río Hurtado',
			'salamanca'    => 'Salamanca',
			'vicuna'       => 'Vicuña',
		) ),
		'VS' => _bgmg_chile_comunas( array(
			'algarrobo'      => 'Algarrobo',
			'cabildo'        => 'Cabildo',
			'calera'         => 'Calera',
			'calle_larga'    => 'Calle Larga',
			'cartagena'      => 'Cartagena',
			'casablanca'     => 'Casablanca',
			'catemu'         => 'Catemu',
			'concon'         => 'Concón',
			'el_quisco'      => 'El Quisco',
			'el_tabo'        => 'El Tabo',
			'hijuelas'       => 'Hijuelas',
			'isla_de_pascua' => 'Isla de Pascua',
			'juan_fernandez' => 'Juan Fernández',
			'la_cruz'        => 'La Cruz',
			'la_ligua'       => 'La Ligua',
			'limache'        => 'Limache',
			'llaillay'       => 'Llaillay',
			'los_andes'      => 'Los Andes',
			'nogales'        => 'Nogales',
			'olmue'          => 'Olmué',
			'panquehue'      => 'Panquehue',
			'papudo'         => 'Papudo',
			'petorca'        => 'Petorca',
			'puchuncavi'     => 'Puchuncaví',
			'putaendo'       => 'Putaendo',
			'quillota'       => 'Quillota',
			'quilpue'        => 'Quilpué',
			'quintero'       => 'Quintero',
			'rinconada'      => 'Rinconada',
			'san_antonio'    => 'San Antonio',
			'san_esteban'    => 'San Esteban',
			'san_felipe'     => 'San Felipe',
			'santa_maria'    => 'Santa María',
			'santo_domingo'  => 'Santo Domingo',
			'valparaiso'     => 'Valparaíso',
			'villa_alemana'  => 'Villa Alemana',
			'vina_del_mar'   => 'Viña del Mar',
			'zapallar'       => 'Zapallar',
		) ),
		'RM' => _bgmg_chile_comunas( array(
			'alhue'                => 'Alhué',
			'buin'                 => 'Buin',
			'calera_de_tango'      => 'Calera de Tango',
			'cerrillos'            => 'Cerrillos',
			'cerro_navia'          => 'Cerro Navia',
			'colina'               => 'Colina',
			'conchali'             => 'Conchalí',
			'curacavi'             => 'Curacaví',
			'el_bosque'            => 'El Bosque',
			'el_monte'             => 'El Monte',
			'estacion_central'     => 'Estación Central',
			'huechuraba'           => 'Huechuraba',
			'independencia'        => 'Independencia',
			'isla_de_maipo'        => 'Isla de Maipo',
			'la_cisterna'          => 'La Cisterna',
			'la_florida'           => 'La Florida',
			'la_granja'            => 'La Granja',
			'la_pintana'           => 'La Pintana',
			'la_reina'             => 'La Reina',
			'lampa'                => 'Lampa',
			'las_condes'           => 'Las Condes',
			'lo_barnechea'         => 'Lo Barnechea',
			'lo_espejo'            => 'Lo Espejo',
			'lo_prado'             => 'Lo Prado',
			'macul'                => 'Macul',
			'maipu'                => 'Maipú',
			'maria_pinto'          => 'María Pinto',
			'melipilla'            => 'Melipilla',
			'nunoa'                => 'Ñuñoa',
			'padre_hurtado'        => 'Padre Hurtado',
			'paine'                => 'Paine',
			'pedro_aguirre_cerda'  => 'Pedro Aguirre Cerda',
			'penaflor'             => 'Peñaflor',
			'penalolen'            => 'Peñalolén',
			'pirque'               => 'Pirque',
			'providencia'          => 'Providencia',
			'pudahuel'             => 'Pudahuel',
			'puente_alto'          => 'Puente Alto',
			'quilicura'            => 'Quilicura',
			'quinta_normal'        => 'Quinta Normal',
			'recoleta'             => 'Recoleta',
			'renca'                => 'Renca',
			'san_bernardo'         => 'San Bernardo',
			'san_joaquin'          => 'San Joaquín',
			'san_jose_de_maipo'    => 'San José de Maipo',
			'san_miguel'           => 'San Miguel',
			'san_pedro_rm'         => 'San Pedro',
			'san_ramon'            => 'San Ramón',
			'santiago'             => 'Santiago',
			'talagante'            => 'Talagante',
			'tiltil'               => 'Tiltil',
			'vitacura'             => 'Vitacura',
		) ),
		'LI' => _bgmg_chile_comunas( array(
			'chepica'             => 'Chépica',
			'chimbarongo'         => 'Chimbarongo',
			'codegua'             => 'Codegua',
			'coinco'              => 'Coínco',
			'coltauco'            => 'Coltauco',
			'donihue'             => 'Doñihue',
			'graneros'            => 'Graneros',
			'la_estrella'         => 'La Estrella',
			'las_cabras'          => 'Las Cabras',
			'litueche'            => 'Litueche',
			'lolol'               => 'Lolol',
			'machali'             => 'Machalí',
			'malloa'              => 'Malloa',
			'marchihue'           => 'Marchigüe',
			'mostazal'            => 'Mostazal',
			'nancagua'            => 'Nancagua',
			'navidad'             => 'Navidad',
			'olivar'              => 'Olivar',
			'palmilla'            => 'Palmilla',
			'paredones'           => 'Paredones',
			'peralillo'           => 'Peralillo',
			'peumo'               => 'Peumo',
			'pichidegua'          => 'Pichidegua',
			'pichilemu'           => 'Pichilemu',
			'placilla'            => 'Placilla',
			'pumanque'            => 'Pumanque',
			'quinta_de_tilcoco'   => 'Quinta de Tilcoco',
			'rancagua'            => 'Rancagua',
			'rengo'               => 'Rengo',
			'requinoa'            => 'Requínoa',
			'san_fernando'        => 'San Fernando',
			'san_vicente'         => 'San Vicente',
			'santa_cruz'          => 'Santa Cruz',
		) ),
		'ML' => _bgmg_chile_comunas( array(
			'cauquenes'       => 'Cauquenes',
			'chanco'          => 'Chanco',
			'colbun'          => 'Colbún',
			'constitucion'    => 'Constitución',
			'curepto'         => 'Curepto',
			'curico'          => 'Curicó',
			'empedrado'       => 'Empedrado',
			'hualane'         => 'Hualañé',
			'licanten'        => 'Licantén',
			'linares'         => 'Linares',
			'longavi'         => 'Longaví',
			'maule'           => 'Maule',
			'molina'          => 'Molina',
			'parral'          => 'Parral',
			'pelarco'         => 'Pelarco',
			'pelluhue'        => 'Pelluhue',
			'pencahue'        => 'Pencahue',
			'rauco'           => 'Rauco',
			'retiro'          => 'Retiro',
			'rio_claro'       => 'Río Claro',
			'romeral'         => 'Romeral',
			'sagrada_familia' => 'Sagrada Familia',
			'san_clemente'    => 'San Clemente',
			'san_javier'      => 'San Javier',
			'san_rafael'      => 'San Rafael',
			'talca'           => 'Talca',
			'teno'            => 'Teno',
			'vichuquen'       => 'Vichuquén',
			'villa_alegre'    => 'Villa Alegre',
			'yerbas_buenas'   => 'Yerbas Buenas',
		) ),
		'NB' => _bgmg_chile_comunas( array(
			'bulnes'        => 'Bulnes',
			'chillan'       => 'Chillán',
			'chillan_viejo' => 'Chillán Viejo',
			'cobquecura'    => 'Cobquecura',
			'coelemu'       => 'Coelemu',
			'coihueco'      => 'Coihueco',
			'el_carmen'     => 'El Carmen',
			'ninhue'        => 'Ninhue',
			'niquen'        => 'Ñiquén',
			'pemuco'        => 'Pemuco',
			'pinto'         => 'Pinto',
			'portezuelo'    => 'Portezuelo',
			'quillon'       => 'Quillón',
			'quirihue'      => 'Quirihue',
			'ranquil'       => 'Ránquil',
			'san_carlos'    => 'San Carlos',
			'san_fabian'    => 'San Fabián',
			'san_ignacio'   => 'San Ignacio',
			'san_nicolas'   => 'San Nicolás',
			'treguaco'      => 'Treguaco',
			'yungay'        => 'Yungay',
		) ),
		'BI' => _bgmg_chile_comunas( array(
			'alto_biobio'        => 'Alto Biobío',
			'antuco'             => 'Antuco',
			'arauco'             => 'Arauco',
			'cabrero'            => 'Cabrero',
			'canete'             => 'Cañete',
			'chiguayante'        => 'Chiguayante',
			'concepcion'         => 'Concepción',
			'contulmo'           => 'Contulmo',
			'coronel'            => 'Coronel',
			'curanilahue'        => 'Curanilahue',
			'florida'            => 'Florida',
			'hualpen'            => 'Hualpén',
			'hualqui'            => 'Hualqui',
			'laja'               => 'Laja',
			'lebu'               => 'Lebu',
			'los_alamos'         => 'Los Álamos',
			'los_angeles'        => 'Los Ángeles',
			'lota'               => 'Lota',
			'mulchen'            => 'Mulchén',
			'nacimiento'         => 'Nacimiento',
			'negrete'            => 'Negrete',
			'penco'              => 'Penco',
			'quilaco'            => 'Quilaco',
			'quilleco'           => 'Quilleco',
			'san_pedro_de_la_paz' => 'San Pedro de la Paz',
			'san_rosendo'        => 'San Rosendo',
			'santa_barbara'      => 'Santa Bárbara',
			'santa_juana'        => 'Santa Juana',
			'talcahuano'         => 'Talcahuano',
			'tirua'              => 'Tirúa',
			'tome'               => 'Tomé',
			'tucapel'            => 'Tucapel',
			'yumbel'             => 'Yumbel',
		) ),
		'AR' => _bgmg_chile_comunas( array(
			'angol'           => 'Angol',
			'carahue'         => 'Carahue',
			'cholchol'        => 'Cholchol',
			'collipulli'      => 'Collipulli',
			'cunco'           => 'Cunco',
			'curacautin'      => 'Curacautín',
			'curarrehue'      => 'Curarrehue',
			'ercilla'         => 'Ercilla',
			'freire'          => 'Freire',
			'galvarino'       => 'Galvarino',
			'gorbea'          => 'Gorbea',
			'lautaro'         => 'Lautaro',
			'loncoche'        => 'Loncoche',
			'lonquimay'       => 'Lonquimay',
			'los_sauces'      => 'Los Sauces',
			'lumaco'          => 'Lumaco',
			'melipeuco'       => 'Melipeuco',
			'nueva_imperial'  => 'Nueva Imperial',
			'padre_las_casas' => 'Padre Las Casas',
			'perquenco'       => 'Perquenco',
			'pitrufquen'      => 'Pitrufquén',
			'pucon'           => 'Pucón',
			'puren'           => 'Purén',
			'renaico'         => 'Renaico',
			'saavedra'        => 'Saavedra',
			'temuco'          => 'Temuco',
			'teodoro_schmidt' => 'Teodoro Schmidt',
			'tolten'          => 'Toltén',
			'traiguen'        => 'Traiguén',
			'victoria'        => 'Victoria',
			'vilcun'          => 'Vilcún',
			'villarrica'      => 'Villarrica',
		) ),
		'LR' => _bgmg_chile_comunas( array(
			'corral'      => 'Corral',
			'futrono'     => 'Futrono',
			'la_union'    => 'La Unión',
			'lago_ranco'  => 'Lago Ranco',
			'lanco'       => 'Lanco',
			'los_lagos'   => 'Los Lagos',
			'mafil'       => 'Máfil',
			'mariquina'   => 'Mariquina',
			'paillaco'    => 'Paillaco',
			'panguipulli' => 'Panguipulli',
			'rio_bueno'   => 'Río Bueno',
			'valdivia'    => 'Valdivia',
		) ),
		'LL' => _bgmg_chile_comunas( array(
			'ancud'                  => 'Ancud',
			'calbuco'                => 'Calbuco',
			'castro'                 => 'Castro',
			'chaiten'                => 'Chaitén',
			'chonchi'                => 'Chonchi',
			'cochamo'                => 'Cochamó',
			'curaco_de_velez'        => 'Curaco de Vélez',
			'dalcahue'               => 'Dalcahue',
			'fresia'                 => 'Fresia',
			'frutillar'              => 'Frutillar',
			'futaleufu'              => 'Futaleufú',
			'hualaihue'              => 'Hualaihué',
			'llanquihue'             => 'Llanquihue',
			'los_muermos'            => 'Los Muermos',
			'maullin'                => 'Maullín',
			'osorno'                 => 'Osorno',
			'palena'                 => 'Palena',
			'puerto_montt'           => 'Puerto Montt',
			'puerto_octay'           => 'Puerto Octay',
			'puerto_varas'           => 'Puerto Varas',
			'puqueldon'              => 'Puqueldón',
			'purranque'              => 'Purranque',
			'puyehue'                => 'Puyehue',
			'queilen'                => 'Queilén',
			'quellon'                => 'Quellón',
			'quemchi'                => 'Quemchi',
			'quinchao'               => 'Quinchao',
			'rio_negro'              => 'Río Negro',
			'san_juan_de_la_costa'   => 'San Juan de la Costa',
			'san_pablo'              => 'San Pablo',
		) ),
		'AI' => _bgmg_chile_comunas( array(
			'aysen'       => 'Aysén',
			'chile_chico' => 'Chile Chico',
			'cisnes'      => 'Cisnes',
			'cochrane'    => 'Cochrane',
			'coyhaique'   => 'Coyhaique',
			'guaitecas'   => 'Guaitecas',
			'lago_verde'  => 'Lago Verde',
			'ohiggins'    => "O'Higgins",
			'rio_ibanez'  => 'Río Ibáñez',
			'tortel'      => 'Tortel',
		) ),
		'MA' => _bgmg_chile_comunas( array(
			'antartica'         => 'Antártica',
			'cabo_de_hornos'    => 'Cabo de Hornos',
			'laguna_blanca'     => 'Laguna Blanca',
			'natales'           => 'Natales',
			'porvenir'          => 'Porvenir',
			'primavera'         => 'Primavera',
			'punta_arenas'      => 'Punta Arenas',
			'rio_verde'         => 'Río Verde',
			'san_gregorio'      => 'San Gregorio',
			'timaukel'          => 'Timaukel',
			'torres_del_paine'  => 'Torres del Paine',
		) ),
	);

	return $cache;
}

/**
 * Devuelve TODAS las comunas como mapa slug → nombre. Útil para buscar el
 * nombre legible de un slug guardado en BD.
 */
function bgmg_chile_get_comunas_flat() {
	static $flat = null;
	if ( null !== $flat ) {
		return $flat;
	}
	$flat = array();
	foreach ( bgmg_chile_get_comunas_por_region() as $comunas ) {
		foreach ( $comunas as $c ) {
			$flat[ $c['slug'] ] = $c['nombre'];
		}
	}
	return $flat;
}

/**
 * Devuelve el nombre humano de una comuna por su slug, o '' si no existe.
 */
function bgmg_chile_get_comuna_nombre( $slug ) {
	$flat = bgmg_chile_get_comunas_flat();
	return isset( $flat[ $slug ] ) ? $flat[ $slug ] : '';
}

/**
 * Verifica que un slug de comuna pertenece efectivamente a una región dada.
 * Defensa contra POST manipulado.
 */
function bgmg_chile_comuna_pertenece_a_region( $comuna_slug, $region_code ) {
	$mapa = bgmg_chile_get_comunas_por_region();
	if ( ! isset( $mapa[ $region_code ] ) ) {
		return false;
	}
	foreach ( $mapa[ $region_code ] as $c ) {
		if ( $c['slug'] === $comuna_slug ) {
			return true;
		}
	}
	return false;
}

/**
 * Devuelve la región a la que pertenece una comuna (slug), o '' si no se halla.
 */
function bgmg_chile_get_region_de_comuna( $comuna_slug ) {
	foreach ( bgmg_chile_get_comunas_por_region() as $region_code => $comunas ) {
		foreach ( $comunas as $c ) {
			if ( $c['slug'] === $comuna_slug ) {
				return $region_code;
			}
		}
	}
	return '';
}
