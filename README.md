# CakeFTP: A FTP/SFTP Plugin for CakePHP

## Features

* Can list/upload/download/delete remote files with FTP or SFTP (uses phpseclib)
* Built-in ftp client

## Manual

For info on how to install and use this plugin please refer to the [wiki](http://github.com/shama/cakeftp/wiki).

## Issues

Please report any issues you have with the plugin to the [issue tracker](http://github.com/shama/cakeftp/issues) on github. **This is a beta release so please be gentle!**

## License

CakeFTP is offered under an [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Copyright

2010 Kyle Robinson Young, [kyletyoung.com](http://kyletyoung.com)

If you found this release useful please let the author know! Follow on [Twitter](http://twitter.com/kyletyoung)

## Thanks

* TerraFrost (Jim Wigginton), for the awesome [phpseclib](http://phpseclib.sourceforge.net/).
* Ian Tucker @ [Everflight](http://www.everflight.com/), for helping me get this started.

## Roadmap and Known Issues

* Write tests
* Test with Windows (likely doesn't work)
* Ability to enable logging to debug remote server issues
* Build console controller (SSH remote console)
* Recursiveness with threading
* Filtering on find

## Changelog

### 0.1

* Added console support to ftp datasource
* Built ftp client controller and view
* Built ftp model
* Built ftp datasource
* Setup app model and app controller
* Setup basic plugin
