## Plan : Correction .htaccess pour site en sous-dossier

Votre site est dans un sous-dossier (ex : /inve-app/). Pour que les URLs sans extension .php fonctionnent, il faut adapter la règle RewriteBase et les chemins d’erreur.

### Étapes
1. Modifier RewriteBase dans [.htaccess](.htaccess) pour qu’elle corresponde au sous-dossier : RewriteBase /inve-app/
2. Adapter la règle ErrorDocument 404 pour pointer vers /inve-app/pagesweb_cn/404.php
3. Vérifier que les règles de réécriture sont bien appliquées dans le sous-dossier.
4. Tester l’accès à /inve-app/pagesweb_cn/trial_form (sans .php) et à d’autres fichiers similaires.
5. Mettre à jour les liens internes dans le code pour pointer vers les URLs sans extension.

### Points à considérer
1. Le sous-dossier doit être correctement référencé dans toutes les règles .htaccess.
2. Les liens internes doivent utiliser le chemin relatif ou absolu avec le sous-dossier.
3. Si d’autres .htaccess existent dans des sous-dossiers, vérifier qu’ils ne contredisent pas ces règles.
