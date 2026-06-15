#include "Database.h"
#include "SearchQuery.hpp"
#include "WPConfig.h"
#include "mysql.h"
#include "wpdb.h"
#include <cstring>
#include "HELPME.hpp"

#define REPLACE "byttmeg"

#define BIND_PREFIX(X) X.binds.add_bind(config.prefix.c_str(), config.prefix.size(), MYSQL_TYPE_STRING, "", "");

int main()
{
    const char *names[1] = {"aid"};
    WPConfig config{};
    Database database(config);


    const char* sql = "select ID from wp_posts where post_mime_type LIKE 'image/%' AND post_type = 'attachment'";
    mysql_query(database.m_mysql, sql);
    MYSQL_RES* result =  null;
    MYSQL_ROW row = null;
    if (!(result = mysql_store_result(database.m_mysql))) exit(-1);
    while ((row = mysql_fetch_row(result)) != null) {
        // Figure out how to cast this correctly
        auto attachment_id = static_cast<int>(std::stol(row[0]));


    }
    mysql_free_result(result);
}




// pass to funcion is QUERY and BIND
// then execute that
// QUERY
// BIND
// STMT
// OBJECT
// OBJECT.FUNCTION
