#include "Database.h"
#include "WPConfig.h"
#include "mysql.h"
#include "wpdb.h"
#include <cstdlib>
#include <cstring>
#include <fstream>
#include "HELPME.hpp"
#include "QueryMaster.hpp"

#define REPLACE "byttmeg"

#define BIND_PREFIX(X) X.binds.add_bind(config.prefix.c_str(), config.prefix.size(), MYSQL_TYPE_STRING, "", "");

int main()
{
    std::ofstream unused_list { "unused", std::ofstream::out | std::ofstream::trunc };
    if (!unused_list) {
        exit(-4);
    }
    const char *names[1] = {"aid"};
    WPConfig config{};
    Database database(config);
    QueryMaster didler(&database, &config, names);
    didler.add_query("SELECT post_id FROM ?postmeta WHERE meta_key = '_thumbnail_id' and meta_value = :aid");
    didler.add_query("SELECT post_id FROM ?postmeta WHERE meta_value = '%\":aid\"%' OR meta_value = '%i::aid%' OR meta_value = '%attachment_id\";i::aid' AND meta_key NOT LIKE '_%%'");

    didler.add_query("SELECT ID FROM ?posts WHERE post_type != 'revision' AND post_type != 'attachment' AND post_content LIKE '%\"::aid,%'");
    didler.add_query("SELECT ID FROM ?posts WHERE post_status = 'publish' AND (post_content LIKE '%wp-image-:aid%' OR post_content LIKE ':aid=\":aid\"%' OR post_content LIKE '%data-id=\":aid\"%');");

    didler.add_query("SELECT option_id FROM ?options WHERE option_value LIKE '%$:aid%'");
    didler.add_query("SELECT option_id FROM ?options WHERE option_name LIKE 'widget_%%' AND option_value LIKE '%:aid%'");
    didler.add_query("SELECT option_id FROM ?options WHERE option_name LIKE 'theme_mods_%%' AND option_value LIKE '%:aid%'");

    const char* sql = "select ID from wp_posts where post_mime_type LIKE 'image/%' AND post_type = 'attachment'";
    mysql_query(database.m_mysql, sql);
    MYSQL_RES* result =  null;
    MYSQL_ROW row = null;
    if (!(result = mysql_store_result(database.m_mysql))) exit(-1);
    while ((row = mysql_fetch_row(result)) != null) {
        auto attachment_id = static_cast<int>(std::stol(row[0])); // SET THE ID AND CHANGE IT ALSO HERE SOMETHING
        didler.change_aid(attachment_id);
        for (int i = 0; i <= didler.queries.size(); i++) {
            if (didler.queries[i].execute() ) {
                unused_list << attachment_id;
            }
        }
    }
    mysql_free_result(result);
}
