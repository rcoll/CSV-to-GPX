<?php

/**
 * Convert a CSV in the below format into a Garmin GPX file
 *
 * @csvformat (n/a),Name,Comment,Date,(n/a),DmmLat,DmmLon,Depth,(n/a)
 *
 * ### CLI Usage
 *
 * php csv-to-gpx.php /some/input/file.csv /some/output/file.gpx [category]
 *
 * ### PHP Usage
 * 
 * $csv = file_get_contents( '/some/input/file.csv' );
 * $xml = CSV_To_GPX_Converter::convert( $csv );
 * var_dump( $xml );
 */
class CSV_To_GPX_Converter {

	/**
	 * Singleton instance holder
	 *
	 * @var mixed
	 * @access public
	 * @static
	 */
	static $instance = null;

	/**
	 * The category to group the waypoints into
	 *
	 * @var string
	 * @access public
	 * @static
	 */
	static $category = 'CSVTOGPX';

	/**
	 * Singleton init
	 *
	 * @uses self::$instance
	 *
	 * @return Object
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 *
	 * @global $argv
	 * @uses self::convert()
	 * 
	 * @access public
	 */
	function __construct() {
		// Stand alone mode when run from the command line
		if ( 'cli' === php_sapi_name() ) {
			global $argv;

			// Bail if we don't have enough arguments
			if ( count( $argv ) < 3 ) {
				die( "Usage: php csv-to-gpx.php /some/input/file.csv /some/output/file.gpx [category]\n" );
			}

			// Bail if the input file does not exist
			if ( ! file_exists( $argv[1] ) ) {
				die( "Input file \"{$argv[1]}\" does not exist!\n" );
			}

			// Set the grouping category if needed
			if ( isset( $argv[3] ) ) {
				self::$category = (string) $argv[3];
			}

			// Get the input file and convert
			$csv = file_get_contents( $argv[1] );
			$xml = self::convert( $csv );

			// Write to the output file
			file_put_contents( $argv[2], $xml );
		}
	}

	/**
	 * Convert a ddmm.mmm point to dd.dddddd (dec-min to dec)
	 * 
	 * @param string $point A decimal-minute point in ddmm.mmm format
	 *
	 * @access public
	 * @static
	 *
	 * @return float The resulting decimal point in dd.dddd format
	 */
	public static function dmm_to_dec( $point ) {
		// Explode by whitespace
		$point = explode( " ", $point );

		// Negative if North or West
		$multiplier = ( $point[0] < 0 ) ? -1 : 1;

		// Do the math and return the result
		return floatval( abs( $point[0] ) + ( $point[1] / 60 ) ) * $multiplier;
	}

	/**
	 * Get the current time in Zulu/UTC format
	 *
	 * @access public
	 * @static
	 *
	 * @return string The current time in zulu/utc format
	 */
	public static function current_zulu_time() {
		date_default_timezone_set( 'UTC' );

		$date = date( 'Y-m-d', time() );
		$time = date( 'H:i:s', time() );

		return sprintf( "%sT%sZ", $date, $time );
	}

	/**
	 * Build the GPX file header
	 * 
	 * @param float $maxlat Maximum latitude in result set
	 * @param float $maxlon Maximum longitude in result set
	 * @param float $minlat Minimum latitude in result set
	 * @param float $minlon Minimum longitude in result set
	 *
	 * @access public
	 * @static
	 *
	 * @return string The GPX file header
	 */
	public static function build_header( $maxlat, $maxlon, $minlat, $minlon ) {
		// Get current Z time
		$time = self::current_zulu_time();

		// Build a standard GPX header
		$header = '';
		$header .= "<?xml version=\"1.0\"?>\n";
		$header .= "<gpx xmlns=\"http://www.topografix.com/GPX/1/1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:wptx1=\"http://www.garmin.com/xmlschemas/WaypointExtension/v1\" xmlns:gpxtrx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\" xmlns:gpxtpx=\"http://www.garmin.com/xmlschemas/TrackPointExtension/v1\" xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\" xmlns:trp=\"http://www.garmin.com/xmlschemas/TripExtensions/v1\" xmlns:adv=\"http://www.garmin.com/xmlschemas/AdventuresExtensions/v1\" xmlns:prs=\"http://www.garmin.com/xmlschemas/PressureExtension/v1\" creator=\"Garmin Desktop App\" version=\"1.1\" xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd http://www.garmin.com/xmlschemas/WaypointExtension/v1 http://www8.garmin.com/xmlschemas/WaypointExtensionv1.xsd http://www.garmin.com/xmlschemas/TrackPointExtension/v1 http://www.garmin.com/xmlschemas/TrackPointExtensionv1.xsd http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www8.garmin.com/xmlschemas/GpxExtensionsv3.xsd http://www.garmin.com/xmlschemas/ActivityExtension/v1 http://www8.garmin.com/xmlschemas/ActivityExtensionv1.xsd http://www.garmin.com/xmlschemas/AdventuresExtensions/v1 http://www8.garmin.com/xmlschemas/AdventuresExtensionv1.xsd http://www.garmin.com/xmlschemas/PressureExtension/v1 http://www.garmin.com/xmlschemas/PressureExtensionv1.xsd http://www.garmin.com/xmlschemas/TripExtensions/v1 http://www.garmin.com/xmlschemas/TripExtensionsv1.xsd\">\n";
		$header .= "\n";
		$header .= "  <metadata>\n";
		$header .= "    <link href=\"http://www.garmin.com\">\n";
		$header .= "      <text>Garmin International</text>\n";
		$header .= "    </link>\n";
		$header .= "    <time>{$time}</time>\n";
		$header .= "    <bounds maxlat=\"{$maxlat}\" maxlon=\"{$maxlon}\" minlat=\"{$minlat}\" minlon=\"{$minlon}\"/>\n";
		$header .= "  </metadata>\n\n";

		return $header;
	}

	/**
	 * Build a waypoint in GPX format from a location array
	 * 
	 * @param array $location A location array
	 *
	 * @uses self::current_zulu_time()
	 * 
	 * @access public
	 * @static
	 *
	 * @return string GPX format of a point
	 */
	public static function build_waypoint( $location ) {
		// Get current Z time
		$time = self::current_zulu_time();

		// Get the waypoint category
		$category = self::$category;

		// Build the waypoint XML
		$waypoint = '';
		$waypoint .= "  <wpt lat=\"{$location['lat']}\" lon=\"{$location['lon']}\">\n";
		$waypoint .= "    <time>{$time}</time>\n";
		$waypoint .= "    <name>{$location['name']}</name>\n";
		$waypoint .= "    <cmt>{$location['comment']}</cmt>\n";
		$waypoint .= "    <desc>{$location['comment']}</desc>\n";
		$waypoint .= "    <sym>Fishing Area 1</sym>\n";
		$waypoint .= "    <type>user</type>\n";
		$waypoint .= "    <extensions>\n";
		$waypoint .= "      <gpxx:WaypointExtension>\n";
		$waypoint .= "        <gpxx:Depth>{$location['depth']}</gpxx:Depth>\n";
		$waypoint .= "        <gpxx:DisplayMode>SymbolAndName</gpxx:DisplayMode>\n";
		$waypoint .= "        <gpxx:Categories>\n";
		$waypoint .= "          <gpxx:Category>{$category}</gpxx:Category>\n";
		$waypoint .= "        </gpxx:Categories>\n";
		$waypoint .= "      </gpxx:WaypointExtension>\n";
		$waypoint .= "      <ctx:CreationTimeExtension xmlns:ctx=\"http://www.garmin.com/xmlschemas/CreationTimeExtension/v1\">\n";
		$waypoint .= "        <ctx:CreationTime>{$time}</ctx:CreationTime>\n";
		$waypoint .= "      </ctx:CreationTimeExtension>\n";
		$waypoint .= "    </extensions>\n";
		$waypoint .= "  </wpt>\n\n";

		return $waypoint;
	}

	/**
	 * Build a standard GPX footer
	 *
	 * @access public
	 * @static
	 *
	 * @return string Standard GPX footer
	 */
	public static function build_footer() {
		return "</gpx>";
	}

	/**
	 * Convert a string in CSV format into a full GPX file
	 *
	 * @param string $csv Comma separated values with \n line termination
	 *
	 * @uses self::dmm_to_dec()
	 * @uses self::build_waypoint()
	 * @uses self::build_header()
	 * @uses self::build_footer()
	 *
	 * @access public
	 * @static
	 *
	 * @return string GPX waypoints in XML format
	 */
	public static function convert( $csv ) {
		// Break the file into lines
		$lines = explode( "\n", $csv );

		// Initialize our output variable
		$waypoints = '';

		// Min and max values to be passed into file header
		$maxlat = 0;
		$maxlon = 0;
		$minlat = 0;
		$minlon = 0;

		// Loop through CSV lines
		foreach ( $lines as $line ) {
			// Skip this line if empty
			if ( empty( trim( $line ) ) ) {
				continue;
			}

			// Break the line into fields
			$fields = explode( ",", $line );

			// Convert the DMM lat/lon to Decimal format for GPX
			$lat = self::dmm_to_dec( $fields[5] );
			$lon = self::dmm_to_dec( $fields[6] );

			// Build the waypoint XML from the CSV fields
			$waypoint = self::build_waypoint( array(
				'id'      => (int) $fields[0],
				'name'    => (string) $fields[1], 
				'comment' => (string) $fields[2], 
				'date'    => (string) $fields[3], 
				'prot'    => (bool) ( ! empty( $fields[4] ) ) ? true : false, 
				'lat'     => (float) $lat,
				'lon'     => (float) $lon,
				'depth'   => (int) $fields[7], 
				'relief'  => (int) $fields[8],  
			));

			// Set maximum latitude if needed
			if ( $lat > 0 && $lat > $maxlat ) {
				$maxlat = $lat;
			} 

			// Set minimum latitude if needed
			if ( $lat < 0 && $lat < $minlat ) {
				$minlat = $lat;
			}

			// Set maximum longitude if needed
			if ( $lon > 0 && $lon > $maxlon ) {
				$maxlon = $lon;
			}

			// Set minimum longitude if needed
			if ( $lon < 0 && $lon < $minlon ) {
				$minlon = $lon;
			}

			// Append this waypoint to the list
			$waypoints .= $waypoint;
		}
	
		// Build the standard header and footer		
		$header = self::build_header( $maxlat, $maxlon, $minlat, $minlon );
		$footer = self::build_footer();

		// Now we have a full file, so we return it and we're done!
		return $header . $waypoints . $footer;
	}

}

CSV_To_GPX_Converter::init();
