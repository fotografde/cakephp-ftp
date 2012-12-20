# CakeFTP: A FTP/SFTP Plugin for CakePHP

Requires: CakePHP 2.0+, PHP 5.2+

## Features

* Can list/upload/download/delete remote files with FTP or SFTP (uses phpseclib)
* Built-in ftp client

## Manual

For info on how to install and use this plugin please refer to the [wiki](http://github.com/shama/cakeftp/wiki).

## Issues

Please report any issues you have with the plugin to the [issue tracker](http://github.com/shama/cakeftp/issues) on github.

## License

CakeFTP is offered under an [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Copyright

2011 Kyle Robinson Young, [dontkry.com](http://dontkry.com)

If you found this release useful please let the author know! Follow on [Twitter](http://twitter.com/shamakry)

## Thanks

* TerraFrost (Jim Wigginton), for the awesome [phpseclib](http://phpseclib.sourceforge.net/).
* Ian Tucker @ [Everflight](http://www.everflight.com/), for helping me get this started.

## Roadmap and Known Issues

* Write more tests
* Test with Windows (likely doesn't work)
* Ability to enable logging to debug remote server issues
* Build console controller (SSH remote console)
* Recursiveness with threading
* Filtering on find

## Changelog

### 0.2

* Added ability to override FtpSource::_parsels()
* Upgraded for CakePHP 2.0
* Updated to phpseclib 0.3.1

### 0.1

* Added port to config
* Fixed issue with filenames and spaces
* Fixed 24-hour date format issue
* Fixed issue with caching path
* Separated client view into a ftp helper
* Added console support to ftp datasource
* Built ftp client controller and view
* Built ftp model
* Built ftp datasource
* Setup app model and app controller
* Setup basic plugin
