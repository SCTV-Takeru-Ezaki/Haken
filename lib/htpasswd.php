<?php
define("HTPASSWDFILE", "/home/pituser/.htpasswd");

// Loads htpasswd file into an array of form
// Array( username => crypted_pass, ... )
function loadHtpasswd()
{
  if ( !file_exists(HTPASSWDFILE))
      return Array();

  $res = Array();
  foreach(file(HTPASSWDFILE) as $l)
  {
    $array = explode(':',$l);
    $user = $array[0];
    $pass = chop($array[1]);
    $res[$user] = $pass;
  }
  return $res;
}

// Saves the array given by loadHtpasswd
// Returns true on success, false on failure
function saveHtpasswd( $pass_array )
{
  $result = true;

  ignore_user_abort(true);
  $fp = fopen(HTPASSWDFILE, "w+");
  if (flock($fp, LOCK_EX))
  {
    while( list($u,$p) = each($pass_array))
      fputs($fp, "$u:$p\n");
    flock($fp, LOCK_UN); // release the lock
  }
  else
  {
    trigger_error("Could not save (lock) .htpasswd", E_USER_WARNING);
    $result = false;
  }
  fclose($fp);
  ignore_user_abort(false);
  return $result;
}

// Generates a htpasswd compatible crypted password string.
function randSaltCrypt( $pass )
{
  $salt = "";
  mt_srand((double)microtime()*1000000);
  for ($i=0; $i<CRYPT_SALT_LENGTH; $i++)
    $salt .= substr("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./", mt_rand() & 63, 1);
  return crypt($pass, $salt);
}

// Generates a htpasswd compatible sha1 password hash
function randSaltSha1( $pass )
{
  mt_srand((double)microtime()*1000000);
  $salt = pack("CCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand());
  return "{SSHA}" . base64_encode(pack("H*", sha1($pass . $salt)) . $salt);
}

// Generate a SHA1 password hash *without* salt
function nonSaltedSha1( $pass )
{
  return "{SHA}" . base64_encode(pack("H*", sha1($pass)));
}

// Returns true if the user exists and the password matches, false otherwise
function testHtpasswd( $pass_array, $user, $pass )
{
  if ( !isset($pass_array[$user]))
      return False;
  $crypted = $pass_array[$user];

  return APR1_MD5::check($pass, $crypted);

}

// Internal test
function internalUnitTest()
{
  $pwds = Array( "Test" => randSaltCrypt("testSecret!"),
                 "fish" => randSaltCrypt("sest Ticret"),
                 "Generated" => "/uieo1ANOvsdA",
                 "Generated2" => "Q3cbHUBgm7aYk");

  assert( testHtpasswd( $pwds, "Test", "testSecret!" ));
  assert( !testHtpasswd( $pwds, "Test", "wrong pass" ));
  assert( testHtpasswd( $pwds, "fish", "sest Ticret" ));
  assert( !testHtpasswd( $pwds, "fish", "wrong pass" ));
  assert( testHtpasswd( $pwds, "Generated", "withHtppasswdCmd" ));
  assert( !testHtpasswd( $pwds, "Generated", "" ));
  assert( testHtpasswd( $pwds, "Generated2", "" ));
  assert( !testHtpasswd( $pwds, "Generated2", "this is wrong too" ));
}

?>