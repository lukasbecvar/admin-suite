#!/bin/bash

# update the documentation
php bin/documentor -d ./src -t docs

# delete the Application package from the index.html
sed -i '/<dt class="phpdocumentor-table-of-contents__entry -package"><a href="packages\/Application.html"><abbr title="\\Application">Application<\/abbr><\/a><\/dt>/d' docs/index.html
