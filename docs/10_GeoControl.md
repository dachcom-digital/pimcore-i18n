# Geo Control

> The Data provider is only required if you want to use the country detection!
>
Follow the [official instructions](https://dev.maxmind.com/geoip/geoipupdate/) for obtaining and updating the GeoIP database.
Store the database file at the location of your choice, the default location used by _geoipupdate_ is `/usr/share/GeoIP/GeoLite2-City.mmdb`

Set the path to the database file in your `parameters.yml` to enable the geo support in Pimcore: 

```yaml
i18n.geo_ip.db_file: /usr/share/GeoIP/GeoLite2-City.mmdb
``` 

To keep the BC, I18n also will check the project path `var/config/GeoLite2-City.mmdb` for legacy reasons.
