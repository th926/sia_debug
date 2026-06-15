#include "Database.h"

Database::Database(WPConfig &config) {
  if ((m_mysql = mysql_init(NULL)) == nullptr) {
    exit(-1);
    // exit panic whatever here.
  }
  mysql_real_connect(m_mysql, config.db_host.c_str(),
    config.db_user.c_str(),
    config.db_password.c_str(),
    config.db_name.c_str(),
    config.db_port, nullptr, 0);
}
Database::~Database() {
  mysql_close(m_mysql);
}
