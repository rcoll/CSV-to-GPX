# CSV to GPX converter

Convert CSV data in the below format into a Garmin GPX file suitable for importing into HomePort.

### CSV field format

```
(n/a),Name,Comment,Date,(n/a),DmmLat,DmmLon,Depth,(n/a)
```

### Command line usage

```
php csv-to-gpx.php /some/input/file.csv /some/output/file.gpx [category]
```

### Usage in a larger PHP program

```
$csv = file_get_contents( '/some/input/file.csv' );
$xml = CSV_To_GPX_Converter::convert( $csv );
do_something_with_data( $xml );
```