
SkeletonPackageInstaller
========================

Composerový instalátor balíčků pro *Skeleton21*

instalátor zajišťuje:
- zkopírování šablon do adresářů se šablonami
- zkopírování assetů do adresáře www
- zkopírování nových migrací do adresáře `migrations` (dělá to pro kažou migraci pouze jednou, nepřepisuje stávající migrace)
- zkopírování testů do adresáře `tests`

připojení balíčku do aplikace je třeba zajistit ručně (viz *SkeletonPackage*)

při kopírování souborů je často ignorován parametr cesty *Vendor* nebo *Package*. to může vést ke kolizím mezi balíčky


překlad souborových cest:
-------------------------

šablony:
- původní cesta: `vendor/{Vendor}/{Package}/src/templates/{Presenter}/default.latte`
- nová cesta: `app/templates/{Presenter}/package/default.latte`

šablony v modulech:
- původní cesta: `vendor/{Vendor}/{Package}/src/{XyzModule}/templates/{Presenter}/default.latte`
- nová cesta: `app/{XyzModule}/templates/{Presenter}/package/default.latte`

assety:
- původní cesta: `vendor/{Vendor}/{Package}/www/css/my.css`
- nová cesta: `www/css/my.css`

migrace:
- původní cesta: `vendor/{Vendor}/{Package}/migrations/struct/2013-01-01-users.sql`
- původní cesta: `migrations/struct/2013-01-01-users.sql`

testy:
- původní cesta: `vendor/{Vendor}/{Package}/tests/cases/Unit/{Sub/Dir}/Test.php`
- nová cesta: `tests/cases/Unit/{Package}/{Sub/Dir}/Test.php`

