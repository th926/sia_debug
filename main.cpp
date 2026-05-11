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

// DB_NAME
// DB_USER
// DB_PASSWORD
// DB_HOST
std::string regex_helper(std::string constant, std::string &content) {
    std::string regex_str;
    if (constant == "DB_HOST") { //(?:define\( *'DB_HOST', *')(\w+)(?::?)([0-9]*)(?:' *\)); this is the right one
        regex_str = R"((?:define\( *')" + constant + R"(', *')(\w+)(?::)([0-9]+)(?:' *\));)";
    } else {
        regex_str = R"((?:define\( *')" + constant + R"(', *')(\w+)(?:' *\);))";
    }
    std::regex test = std::regex(regex_str);
    std::smatch m;
    if (std::regex_search(content, m, test)) {
        return m[1];
    }
    return std::string();
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
        std::regex table_regex = std::basic_regex(R"((?:\$table_prefix *= *')(\w+)(?:' *;))", std::regex::basic);
        db_name = regex_helper("DB_NAME", in_config);
        db_user = regex_helper("DB_USER", in_config);
        db_password = regex_helper("DB_PASSWORD", in_config);
        db_host = regex_helper("DB_HOST", in_config);
    }
    void print() {
        std::cout << db_name;
        std::cout << db_password;
        std::cout << db_user;
        std::cout << db_host;
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
