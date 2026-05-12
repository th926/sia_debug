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

// check if file exists first
std::string slurp(std::ifstream& in) {
    std::ostringstream sstr;
    sstr << in.rdbuf();
    return sstr.str();
}

std::string regex_helper(std::string constant, std::string &content) {
    //R"((?:define\( *')" + constant + R"(', *')(\w+)(?::?)([0-9]*)(?:' *\));)"
    std::string things = R"((define\( *')" + constant + R"(', *')([[:alnum:]_]+):?([[:digit:]]*)' *\);)";
    std::regex test = std::regex(things, std::regex::extended);
    std::smatch m;
    if (std::regex_search(content, m, test)) {
        return m[2];
    }
    return std::string();
}

class WPConfig {
public:
    std::string db_name;
    std::string db_user;
    std::string db_password;
    std::string db_host;

    std::string prefix;

    std::string config_location = "wp-config.php";
public:
    WPConfig() {
        std::ifstream in_configs (config_location, std::ios::in);
        if (!in_configs.is_open()) {
            std::cerr << "There is no wp-config.php in the current directory" << "\n";
            exit(-1);
        }
        std::string in_config = slurp(in_configs);
        try {
            std::regex table_regex = std::basic_regex(R"((?:\$table_prefix *= *')(\w+)(?:' *;))");

            db_name = regex_helper("DB_NAME", in_config);
            db_user = regex_helper("DB_USER", in_config);
            db_password = regex_helper("DB_PASSWORD", in_config);
            db_host = regex_helper("DB_HOST", in_config);
        } catch (std::__1::regex_error &e) {
            std::cerr << e.what() << "\n";
            exit(-2);
        }
    }
};

int main(void) {
    WPConfig config{};
    try {
        mysqlx::Session sess(config.db_host, 33060, config.db_user, config.db_password);
        mysqlx::Schema db = sess.getSchema(config.db_name);
        mysqlx::Table table = db.getTable("wp_posts");
    } catch (const mysqlx::Error &err) {
        std::cerr << "MySQL Error: " << err.what() << std::endl;
    }
    return 0;
}
