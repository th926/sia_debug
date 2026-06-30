#include <iostream>
#include <fstream>
#include <regex>
#include <sstream>

#include "WPConfig.h"

#define MEGAGIGASUPERERROR -3000

WPConfig::WPConfig() {
  std::ifstream in_configs(config_location, std::ios::in);
  if (!in_configs.is_open()) {
    std::cerr << "There is no wp-config.php in the current directory" << "\n";
    exit(-1);
  }
  std::string in_config = slurp(in_configs);
  try {
    std::regex table_regex = std::regex(R"((?:\$table_prefix *= *')(\w+)(?:' *;))");
    std::smatch m;
    auto tableresult = std::regex_search(in_config, m, table_regex);
    if (tableresult) {
        prefix = m[1]; // DOUBLE TRIPLE QUADROBLECHECK THAT THIS IS CORRECt
    } else {
        exit(-3000);
    }

    db_name = regexport("DB_NAME", in_config);
    db_user = regexport("DB_USER", in_config);
    db_password = regexport("DB_PASSWORD", in_config);
    db_host = regexport("DB_HOST", in_config);
    db_port = stoi(regexport("DB_HOST", in_config, true)); // needs to be an image
  } catch (std::regex_error &e) {
    std::cerr << e.what() << "\n";
    exit(-2);
  }
}

/*
 * I don't know why, but c++ regex creates some bullshit match
 * which pushes the amount of results from 3 to 4
 * normally it's the whole match then the groups. Here there
 * is some shitty inbetween so everything is pushed back by one.
 */
std::string WPConfig::regexport(std::string constant, std::string &content, bool return_second_group) {
    //R"((?:define\( *')" + constant + R"(', *')(\w+)(?::?)([0-9]*)(?:' *\));)"
    std::string things = R"((define\( *')" + constant + R"(', *')([[:alnum:]_]+):?([[:digit:]]*)' *\);)";
    std::regex test = std::regex(things, std::regex::extended);
    std::smatch m;

    if (std::regex_search(content, m, test)) {
        return return_second_group ? m[3] : m[2];
    }
    return std::string();
}

std::string WPConfig::slurp(std::ifstream& in) {
    std::ostringstream sstr;
    sstr << in.rdbuf();
    return sstr.str();
}
