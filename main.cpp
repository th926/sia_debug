//#include "mysqlx/devapi/result.h"
#include <mysqlx/xdevapi.h>
#include <iostream>
#include <fstream>
#include <regex>

// to extract
// DB_NAME
// DB_USER
// DB_PASSWORD
// DB_HOST
// $table_prefix

class wpdb {
    public: std::string posts;
    public: std::string postmeta;
    public: std::string options;
    public: std::string prefix;

    wpdb(std::string in_prefix) {
        prefix = in_prefix;
        posts = prefix.append("posts");
        postmeta = prefix.append("postmeta");
        options = prefix.append("options");
    }
    ~wpdb() {}
};

std::string slurp(std::ifstream& in) {
    std::ostringstream sstr;
    sstr << in.rdbuf();
    return sstr.str();
}

class WPConfig {
private:
    std::string db_name;
    std::string db_user;
    std::string db_password;
    std::string db_host;

    std::string prefix;

    std::string config_location = "wp-config.php";
public:
    WPConfig() {
        std::ifstream in_configs (config_location, std::ios::in);
        std::string in_config = slurp(in_configs);
        std::regex table_regex = std::basic_regex("(?:\$table_prefix = ')(.+)(?:')", std::regex::basic);

    }
};

int main(void) {
    // loot wp config.
    try {
        mysqlx::Session sess("127.0.0.1", 33060, "root", "123");
        mysqlx::Schema db = sess.getSchema("absmnew"); // Needs to be figured out from wp_config.php
        mysqlx::Table table = db.getTable("wp_posts");
    } catch (const mysqlx::Error &err) {
        std::cerr << "MySQL Error: " << err.what() << std::endl;
    }
    return 0;
}
