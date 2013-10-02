<?php
  
  unset( $files );
  $d = dir( "." );
  while( false !== ( $entry = $d->read() ) )
  {
    if( is_file( $entry ) )
    {
      if( preg_match( "/.php/", $entry ) )
      {
        $files[] = $entry;
      }
      if( preg_match( "/.css/", $entry ) )
      {
        $files[] = $entry;
      }
    }
  }
  $d->close();
  
  echo "<pre>";
  sort( $files );
  for( $i = 0; $i < count( $files ); $i++ )
  {
    $f = $files[$i];
    $hash = md5_file( $f );
    printf( "%s : %s\n", $hash, $f );
  }
  echo "</pre>";
?>