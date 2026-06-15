#pragma once
#include <string>

class WPConfig {
public:
    std::string db_name;
    std::string db_user;
    std::string db_password;
    std::string db_host;
    unsigned long db_port = 0;

    std::string prefix;

    std::string config_location = "wp-config.php";

    WPConfig();
    std::string slurp(std::ifstream& in);
    std::string regexport(std::string constant, std::string &content, bool return_second_group = false);
};
