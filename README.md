# SIA
## Building the project
### Prerequesites
- CMAKE >= 3.10
- C++ compiler which supports c++17
- Connector/C++
### Build instructions
#### Connector
The connector library needs to be at the following path from root `externals/connector-cpp`. You can build the connector at this path or just place the resulting static library there. Connector/C++ can be found [here](https://dev.mysql.com/downloads/connector/cpp/). Beneath is the build command I utilized. Run these commands in the source directory for Connector/C++.
```sh
cmake -DBUILD_STATIC=ON -DBUNDLE_DEPENDENCIES=ON -DCMAKE_BUILD_TYPE=release && \
make
```
#### Image deleter
```sh
mkdir build && cd build
```
```sh
cmake .. && make
```
This will leave you a binary in the build directory which is the one you will upload to the server. Keep in mind the difference between architecture, OS and so on and adjust the build commands accordingly.
## Testing
Copy the `wp-config.php` from a local site and run it in the build directory. This will run the tool and delete images on that site so be careful!
## Further details
Se the document named `Handover guide.md` contains core details about the program and some architectural information.
## Using the program
First get the system details of the server you're planning to clean. This can be done easily using the php function `php_uname`wich returns the required system information. Based on the information set your compile commands, the `CMAKE_CXX_FLAGS` variable accordingly. Separate the arguments by spaces.


When you've built the program for the target server use `scp` to move the binary onto the server. Move the binary to the root wordpress directory. Run the program the same way you would run any other program. The program should inform you when it's done. Delete the program and make sure that no used images have been deleted!
